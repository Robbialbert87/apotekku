<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Requests\VoidInvoiceRequest;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\InvoiceFile;
use App\Models\Setting;
use App\Models\Supplier;
use App\Services\InvoiceService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function index(Request $request): View
    {
        $includeVoided = $request->boolean('include_voided');

        $invoices = Invoice::query()
            ->with('supplier')
            ->withPaidAmount()
            ->search($request->string('search')->toString())
            ->forSupplier($request->input('supplier_id'))
            ->wherePaymentStatus($request->input('payment_status'))
            ->whereDueStatus($request->input('due_status'))
            ->when(! $includeVoided, fn ($query) => $query->notVoided())
            ->when($includeVoided, fn ($query) => $query)
            ->when($request->input('date_from'), fn ($query, string $date) => $query->whereDate('invoices.invoice_date', '>=', $date))
            ->when($request->input('date_to'), fn ($query, string $date) => $query->whereDate('invoices.invoice_date', '<=', $date))
            ->latest('invoices.invoice_date')
            ->latest('invoices.id')
            ->get();

        return view('invoices.index', [
            'invoices' => $invoices,
            'suppliers' => $this->supplierOptions(),
            'filters' => $request->only([
                'search', 'supplier_id', 'payment_status', 'due_status', 'date_from', 'date_to',
            ]) + ['include_voided' => $includeVoided],
        ]);
    }

    public function create(Request $request): View
    {
        $dueDays = (int) Setting::get('invoice_due_days', '30');

        return view('invoices.create', [
            'invoice' => new Invoice([
                'invoice_date' => today()->toDateString(),
                'due_date' => today()->addDays($dueDays)->toDateString(),
                'supplier_id' => $request->integer('supplier_id') ?: null,
            ]),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
            'items' => collect(),
            'files' => collect(),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoiceService->create(
            $request->validated(),
            $request->user(),
            $request->file('files', []),
        );

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'Invoice berhasil disimpan.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'supplier',
            'items',
            'files.uploader',
            'allocations.payment',
            'creator',
            'updater',
            'voider',
        ]);

        return view('invoices.show', [
            'invoice' => $invoice,
            'logs' => ActivityLog::query()
                ->where('subject_type', $invoice->getMorphClass())
                ->where('subject_id', $invoice->id)
                ->with('user')
                ->latest('id')
                ->limit(15)
                ->get(),
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        abort_if($invoice->isVoided(), 403, 'Invoice yang dibatalkan tidak dapat diubah.');

        return view('invoices.edit', [
            'invoice' => $invoice->load(['items', 'files']),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'items' => $invoice->items,
            'files' => $invoice->files,
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->invoiceService->update(
            $invoice,
            $request->validated(),
            $request->user(),
            $request->file('files', []),
        );

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'Invoice berhasil diperbarui.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        abort_unless(request()->user()?->hasPermission('invoice.update'), 403);

        if ($invoice->allocations()->exists()) {
            return back()->with('error', 'Invoice yang memiliki pembayaran tidak dapat dihapus.');
        }

        $invoice->files()->delete();
        $invoice->items()->delete();
        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('status', 'Invoice berhasil dihapus.');
    }

    public function void(VoidInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->invoiceService->void($invoice, $request->user(), $request->validated('void_reason'));

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'Invoice berhasil dibatalkan.');
    }

    public function destroyFile(Request $request, Invoice $invoice, InvoiceFile $invoiceFile): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('invoice.update'), 403);
        abort_unless((int) $invoiceFile->invoice_id === (int) $invoice->id, 404);

        $this->invoiceService->deleteFile($invoiceFile, $request->user());

        return back()->with('status', 'Lampiran berhasil dihapus.');
    }

    /**
     * @return Collection<int, Supplier>
     */
    private function supplierOptions(): Collection
    {
        return Supplier::query()->orderBy('name')->get(['id', 'name', 'is_active']);
    }
}
