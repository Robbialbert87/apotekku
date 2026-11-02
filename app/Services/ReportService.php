<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'invoices' => 'Laporan Invoice',
        'payments' => 'Laporan Pembayaran',
        'outstanding' => 'Laporan Sisa Tagihan',
        'due' => 'Laporan Jatuh Tempo',
        'suppliers' => 'Laporan Rekap Supplier',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(string $type, array $filters): Builder
    {
        return match ($type) {
            'payments' => $this->paymentsQuery($filters),
            'outstanding' => $this->outstandingQuery($filters),
            'due' => $this->dueQuery($filters),
            'suppliers' => $this->suppliersQuery($filters),
            default => $this->invoicesQuery($filters),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(string $type, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($type, $filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: Collection<int, array<int, string|int>>}
     */
    public function export(string $type, array $filters): array
    {
        $records = $this->query($type, $filters)->get();

        return match ($type) {
            'payments' => [
                'headings' => ['No. Transaksi', 'Tanggal', 'Supplier', 'Metode', 'Referensi', 'Nominal', 'Status'],
                'rows' => $records->map(fn (Payment $payment): array => [
                    $payment->payment_number,
                    $payment->payment_date?->format('d/m/Y') ?? '-',
                    $payment->supplier->name,
                    $payment->method->label(),
                    $payment->reference_number ?? '-',
                    $payment->total_amount,
                    $payment->status->label(),
                ]),
            ],
            'outstanding' => [
                'headings' => ['No. Invoice', 'Supplier', 'Jatuh Tempo', 'Total', 'Dibayar', 'Sisa', 'Status Bayar', 'Status Jatuh Tempo'],
                'rows' => $records->map(fn (Invoice $invoice): array => [
                    $invoice->invoice_number,
                    $invoice->supplier->name,
                    $invoice->due_date?->format('d/m/Y') ?? '-',
                    $invoice->total_amount,
                    $invoice->paid_amount,
                    $invoice->remaining_amount,
                    $invoice->payment_status->label(),
                    $invoice->due_status?->label() ?? '-',
                ]),
            ],
            'due' => [
                'headings' => ['No. Invoice', 'Supplier', 'Tanggal Invoice', 'Jatuh Tempo', 'Sisa', 'Status Jatuh Tempo'],
                'rows' => $records->map(fn (Invoice $invoice): array => [
                    $invoice->invoice_number,
                    $invoice->supplier->name,
                    $invoice->invoice_date?->format('d/m/Y') ?? '-',
                    $invoice->due_date?->format('d/m/Y') ?? '-',
                    $invoice->remaining_amount,
                    $invoice->due_status?->label() ?? '-',
                ]),
            ],
            'suppliers' => [
                'headings' => ['Supplier', 'Kontak', 'Telepon', 'Jumlah Invoice', 'Total Tagihan', 'Total Dibayar', 'Sisa'],
                'rows' => $records->map(fn (Supplier $supplier): array => [
                    $supplier->name,
                    $supplier->contact_name ?? '-',
                    $supplier->phone,
                    $supplier->invoices_count,
                    $supplier->billed_total ?? '0',
                    $this->supplierPaid($supplier),
                    $supplier->outstanding_total ?? '0',
                ]),
            ],
            default => [
                'headings' => ['No. Invoice', 'Supplier', 'Tanggal Invoice', 'Jatuh Tempo', 'Total', 'Dibayar', 'Sisa', 'Status Bayar', 'Status Jatuh Tempo'],
                'rows' => $records->map(fn (Invoice $invoice): array => [
                    $invoice->invoice_number,
                    $invoice->supplier->name,
                    $invoice->invoice_date?->format('d/m/Y') ?? '-',
                    $invoice->due_date?->format('d/m/Y') ?? '-',
                    $invoice->total_amount,
                    $invoice->paid_amount,
                    $invoice->remaining_amount,
                    $invoice->payment_status->label(),
                    $invoice->due_status?->label() ?? '-',
                ]),
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function invoicesQuery(array $filters): Builder
    {
        return Invoice::query()
            ->with('supplier')
            ->withPaidAmount()
            ->search($filters['search'] ?? null)
            ->forSupplier($filters['supplier_id'] ?? null)
            ->wherePaymentStatus($filters['payment_status'] ?? null)
            ->whereDueStatus($filters['due_status'] ?? null)
            ->when($filters['include_voided'] ?? false, fn (Builder $query) => $query)
            ->when(! ($filters['include_voided'] ?? false), fn (Builder $query) => $query->notVoided())
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('invoices.invoice_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('invoices.invoice_date', '<=', $date))
            ->orderByDesc('invoices.invoice_date')
            ->orderByDesc('invoices.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function paymentsQuery(array $filters): Builder
    {
        return Payment::query()
            ->with('supplier')
            ->search($filters['search'] ?? null)
            ->forSupplier($filters['supplier_id'] ?? null)
            ->betweenDates($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->when(
                ($filters['record_status'] ?? 'posted') === 'posted',
                fn (Builder $query) => $query->posted(),
                fn (Builder $query) => $query->when(($filters['record_status'] ?? null) === 'cancelled', fn (Builder $query) => $query->cancelled()),
            )
            ->orderByDesc('payments.payment_date')
            ->orderByDesc('payments.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function outstandingQuery(array $filters): Builder
    {
        return Invoice::query()
            ->outstanding()
            ->with('supplier')
            ->withPaidAmount()
            ->search($filters['search'] ?? null)
            ->forSupplier($filters['supplier_id'] ?? null)
            ->wherePaymentStatus($filters['payment_status'] ?? null)
            ->whereDueStatus($filters['due_status'] ?? null)
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('invoices.due_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('invoices.due_date', '<=', $date))
            ->orderBy('invoices.due_date');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function dueQuery(array $filters): Builder
    {
        return Invoice::query()
            ->outstanding()
            ->with('supplier')
            ->withPaidAmount()
            ->forSupplier($filters['supplier_id'] ?? null)
            ->wherePaymentStatus($filters['payment_status'] ?? null)
            ->whereDueStatus($filters['due_status'] ?? null)
            ->dueBetween($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->orderBy('invoices.due_date');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function suppliersQuery(array $filters): Builder
    {
        return Supplier::query()
            ->search($filters['search'] ?? null)
            ->when($filters['supplier_id'] ?? null, fn (Builder $query, int|string $id) => $query->whereKey($id))
            ->withCount(['invoices' => fn (Builder $query) => $query->notVoided()])
            ->withSum(['invoices as billed_total' => fn (Builder $query) => $query->notVoided()], 'total_amount')
            ->withSum(['invoices as outstanding_total' => fn (Builder $query) => $query->notVoided()->outstanding()], 'total_amount')
            ->withSum(['payments as paid_total' => fn (Builder $query) => $query->posted()], 'total_amount')
            ->orderBy('suppliers.name');
    }

    private function supplierPaid(Supplier $supplier): string
    {
        $paid = Payment::query()
            ->posted()
            ->where('payments.supplier_id', $supplier->id)
            ->sum('payments.total_amount');

        return Money::fromCents(Money::toCents((string) $paid));
    }
}
