<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\CancelPaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with('supplier')
            ->search($request->string('search')->toString())
            ->forSupplier($request->input('supplier_id'))
            ->betweenDates($request->input('date_from'), $request->input('date_to'))
            ->when($request->input('status') === 'cancelled', fn ($query) => $query->cancelled())
            ->when($request->input('status') === 'posted', fn ($query) => $query->posted())
            ->latest('payments.payment_date')
            ->latest('payments.id')
            ->get();

        return view('payments.index', [
            'payments' => $payments,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'supplier_id', 'date_from', 'date_to', 'status']),
        ]);
    }

    public function create(Request $request): View
    {
        $supplierId = $request->integer('supplier_id') ?: null;
        $invoiceId = $request->integer('invoice_id') ?: null;
        $supplier = $supplierId ? Supplier::query()->find($supplierId) : null;

        $invoices = $supplier === null
            ? collect()
            : Invoice::query()
                ->outstanding()
                ->withPaidAmount()
                ->where('supplier_id', $supplier->id)
                ->orderBy('due_date')
                ->get();

        return view('payments.create', [
            'suppliers' => Supplier::query()
                ->where(fn ($query) => $query->where('is_active', true)
                    ->orWhereHas('invoices', fn ($invoice) => $invoice->outstanding()))
                ->orderBy('name')
                ->get(['id', 'name']),
            'supplier' => $supplier,
            'invoices' => $invoices,
            'invoiceId' => $invoiceId,
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $payment = $this->paymentService->create(
            $request->safe()->except(['allocations', 'proof']),
            $request->validated('allocations'),
            $request->user(),
            $request->file('proof'),
        );

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', 'Pembayaran berhasil dicatat.');
    }

    public function show(Payment $payment): View
    {
        $payment->load(['supplier', 'allocations.invoice', 'creator', 'canceller']);

        return view('payments.show', [
            'payment' => $payment,
            'logs' => ActivityLog::query()
                ->where('subject_type', $payment->getMorphClass())
                ->where('subject_id', $payment->id)
                ->with('user')
                ->latest('id')
                ->limit(15)
                ->get(),
        ]);
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        abort_unless(request()->user()?->hasPermission('payment.create'), 403);

        if (! $payment->isCancelled()) {
            $this->paymentService->cancel($payment, request()->user(), 'Dihapus');
        }

        $payment->allocations()->delete();
        $payment->delete();

        return redirect()
            ->route('payments.index')
            ->with('status', 'Pembayaran berhasil dihapus.');
    }

    public function cancel(CancelPaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->paymentService->cancel($payment, $request->user(), $request->validated('cancel_reason'));

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', 'Pembayaran berhasil dibatalkan.');
    }

    public function proof(Request $request, Payment $payment): StreamedResponse
    {
        abort_unless($request->user()->hasPermission('payment.view'), 403);
        abort_if($payment->proof_path === null, 404, 'Bukti pembayaran tidak tersedia.');

        $disk = Storage::disk(config('apotek.upload.disk', 'local'));

        abort_unless($disk->exists($payment->proof_path), 404, 'Bukti pembayaran tidak ditemukan.');

        if ($request->boolean('download')) {
            return $disk->download($payment->proof_path, $payment->proof_original_name ?? 'bukti-pembayaran');
        }

        return $disk->response($payment->proof_path, $payment->proof_original_name ?? 'bukti-pembayaran');
    }
}
