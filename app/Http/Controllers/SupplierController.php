<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->search($request->string('search')->toString())
            ->when($request->string('status')->toString() === 'active', fn ($query) => $query->active())
            ->when($request->string('status')->toString() === 'inactive', fn ($query) => $query->inactive())
            ->withCount('invoices')
            ->orderBy('name')
            ->get();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): View
    {
        return view('suppliers.create', [
            'supplier' => new Supplier(['is_active' => true]),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
        ]);

        $this->activityLogger->record('supplier.created', $supplier, "Supplier {$supplier->name} ditambahkan.");

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', 'Supplier berhasil ditambahkan.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->loadCount('invoices');

        return view('suppliers.show', [
            'supplier' => $supplier,
            'invoices' => $supplier->invoices()
                ->withPaidAmount()
                ->latest('invoice_date')
                ->limit(10)
                ->get(),
            'recentPayments' => $supplier->payments()
                ->posted()
                ->latest('payment_date')
                ->limit(5)
                ->get(),
        ]);
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', ['supplier' => $supplier]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLogger->record('supplier.updated', $supplier, "Supplier {$supplier->name} diperbarui.");

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        abort_unless(request()->user()?->hasPermission('supplier.update'), 403);

        if ($supplier->invoices()->whereHas('allocations')->exists()) {
            return back()->with('error', 'Supplier yang memiliki pembayaran tidak dapat dihapus. Nonaktifkan saja.');
        }

        foreach ($supplier->invoices as $invoice) {
            $invoice->files()->delete();
            $invoice->items()->delete();
            $invoice->allocations()->delete();
            $invoice->delete();
        }

        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('status', 'Supplier berhasil dihapus.');
    }

    public function toggleStatus(Request $request, Supplier $supplier): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('supplier.deactivate'), 403);

        $supplier->update([
            'is_active' => ! $supplier->is_active,
            'updated_by' => $request->user()->id,
        ]);

        $this->activityLogger->record(
            'supplier.status_changed',
            $supplier,
            "Supplier {$supplier->name} ".($supplier->is_active ? 'diaktifkan' : 'dinonaktifkan').'.',
        );

        return back()->with('status', 'Status supplier berhasil diperbarui.');
    }
}
