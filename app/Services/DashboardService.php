<?php

namespace App\Services;

use App\Enums\InvoicePaymentStatus;
use App\Enums\PaymentRecordStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array<string, array{label: string, value: int|string, format: string, icon: string, tone: string}>
     */
    public function metrics(): array
    {
        $receivable = $this->receivableQuery();
        $totalBilled = (clone $receivable)->sum('invoices.total_amount');
        $totalPaid = $this->totalPaid();
        $outstanding = max(0, Money::toCents((string) $totalBilled) - Money::toCents((string) $totalPaid));

        $dueSoon = Invoice::query()
            ->outstanding()
            ->whereDate('invoices.due_date', '>=', today())
            ->whereDate('invoices.due_date', '<=', today()->addDays((int) config('apotek.due_soon_days', 7)))
            ->count();

        $overdue = Invoice::query()
            ->outstanding()
            ->whereDate('invoices.due_date', '<', today())
            ->count();

        return [
            'suppliers' => [
                'label' => 'Supplier Aktif',
                'value' => Supplier::query()->active()->count(),
                'format' => 'number',
                'icon' => 'bi-truck',
                'tone' => 'primary',
            ],
            'invoices' => [
                'label' => 'Invoice Aktif',
                'value' => (clone $receivable)->count(),
                'format' => 'number',
                'icon' => 'bi-receipt',
                'tone' => 'info',
            ],
            'billed' => [
                'label' => 'Total Tagihan',
                'value' => Money::fromCents(Money::toCents((string) $totalBilled)),
                'format' => 'currency',
                'icon' => 'bi-cash-stack',
                'tone' => 'primary',
            ],
            'paid' => [
                'label' => 'Total Dibayar',
                'value' => Money::fromCents(Money::toCents((string) $totalPaid)),
                'format' => 'currency',
                'icon' => 'bi-check2-circle',
                'tone' => 'success',
            ],
            'outstanding' => [
                'label' => 'Sisa Tagihan',
                'value' => Money::fromCents($outstanding),
                'format' => 'currency',
                'icon' => 'bi-wallet2',
                'tone' => 'warning',
            ],
            'due_soon' => [
                'label' => 'Segera Jatuh Tempo',
                'value' => $dueSoon,
                'format' => 'number',
                'icon' => 'bi-clock-history',
                'tone' => 'warning',
            ],
            'overdue' => [
                'label' => 'Invoice Terlambat',
                'value' => $overdue,
                'format' => 'number',
                'icon' => 'bi-exclamation-triangle',
                'tone' => 'danger',
            ],
        ];
    }

    /**
     * Grafik tagihan vs pembayaran enam bulan terakhir.
     *
     * @return array{labels: array<int, string>, billed: array<int, string>, paid: array<int, string>}
     */
    public function monthlyChart(int $months = 6): array
    {
        $start = today()->startOfMonth()->subMonths($months - 1);

        $billed = Invoice::query()
            ->notVoided()
            ->whereDate('invoices.invoice_date', '>=', $start->toDateString())
            ->when(DB::getDriverName() !== 'mysql', function ($query) {
                $query->selectRaw("strftime('%Y-%m', invoices.invoice_date) as period");
            })
            ->when(DB::getDriverName() === 'mysql', function ($query) {
                $query->selectRaw("DATE_FORMAT(invoices.invoice_date, '%Y-%m') as period");
            })
            ->groupBy('period')
            ->pluck('total', 'period');

        $paid = Payment::query()
            ->posted()
            ->whereDate('payments.payment_date', '>=', $start->toDateString())
            ->when(DB::getDriverName() !== 'mysql', function ($query) {
                $query->selectRaw("strftime('%Y-%m', payments.payment_date) as period");
            })
            ->when(DB::getDriverName() === 'mysql', function ($query) {
                $query->selectRaw("DATE_FORMAT(payments.payment_date, '%Y-%m') as period");
            })
            ->groupBy('period')
            ->pluck('total', 'period');

        $labels = [];
        $billedData = [];
        $paidData = [];

        for ($month = 0; $month < $months; $month++) {
            $date = $start->copy()->addMonths($month);
            $key = $date->format('Y-m');

            $labels[] = $date->locale('id')->translatedFormat('M Y');
            $billedData[] = Money::fromCents(Money::toCents((string) ($billed[$key] ?? 0)));
            $paidData[] = Money::fromCents(Money::toCents((string) ($paid[$key] ?? 0)));
        }

        return [
            'labels' => $labels,
            'billed' => $billedData,
            'paid' => $paidData,
        ];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>, colors: array<int, string>}
     */
    public function paymentStatusChart(): array
    {
        $billed = $this->receivableQuery();
        $total = Money::toCents((string) (clone $billed)->sum('invoices.total_amount'));
        $paid = Money::toCents((string) $this->totalPaid());

        $partial = (int) (clone $billed)
            ->whereRaw('(SELECT COALESCE(SUM(pa.amount), 0) FROM payment_allocations pa INNER JOIN payments p ON p.id = pa.payment_id AND p.status = ? WHERE pa.invoice_id = invoices.id) > 0', [PaymentRecordStatus::Posted->value])
            ->count();

        $unpaid = (int) (clone $billed)
            ->whereRaw('(SELECT COALESCE(SUM(pa.amount), 0) FROM payment_allocations pa INNER JOIN payments p ON p.id = pa.payment_id AND p.status = ? WHERE pa.invoice_id = invoices.id) <= 0', [PaymentRecordStatus::Posted->value])
            ->count();

        $activeInvoices = (int) (clone $billed)->count();

        return [
            'labels' => [
                InvoicePaymentStatus::BelumDibayar->label(),
                InvoicePaymentStatus::DibayarSebagian->label(),
                InvoicePaymentStatus::Lunas->label(),
            ],
            'data' => [$unpaid, $partial, max(0, $activeInvoices - $unpaid - $partial)],
            'colors' => ['#ef4444', '#f59e0b', '#22c55e'],
            'total_billed' => Money::fromCents($total),
            'total_paid' => Money::fromCents($paid),
        ];
    }

    /**
     * @return Collection<int, array{name: string, outstanding: string}>
     */
    public function topSuppliers(int $limit = 5): Collection
    {
        return Supplier::query()
            ->select('suppliers.id', 'suppliers.name')
            ->withSum('invoices as billed_total', 'total_amount')
            ->get()
            ->map(function (Supplier $supplier): array {
                $paid = Money::toCents((string) Payment::query()
                    ->posted()
                    ->where('payments.supplier_id', $supplier->id)
                    ->sum('payments.total_amount'));

                $billed = Money::toCents((string) $supplier->billed_total);

                return [
                    'name' => $supplier->name,
                    'outstanding' => Money::fromCents(max(0, $billed - $paid)),
                ];
            })
            ->sortByDesc(fn (array $row): int => Money::toCents($row['outstanding']))
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function latestInvoices(int $limit = 5): Collection
    {
        return Invoice::query()
            ->notVoided()
            ->with('supplier')
            ->withPaidAmount()
            ->latest('invoices.created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function nearestDue(int $limit = 5): Collection
    {
        return Invoice::query()
            ->outstanding()
            ->with('supplier')
            ->withPaidAmount()
            ->whereDate('invoices.due_date', '>=', today())
            ->orderBy('invoices.due_date')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function overdueInvoices(int $limit = 5): Collection
    {
        return Invoice::query()
            ->outstanding()
            ->with('supplier')
            ->withPaidAmount()
            ->whereDate('invoices.due_date', '<', today())
            ->orderBy('invoices.due_date')
            ->limit($limit)
            ->get();
    }

    public function receivableQuery(): Builder
    {
        return Invoice::query()->notVoided();
    }

    private function totalPaid(): int
    {
        return Money::toCents((string) Payment::query()->posted()->sum('payments.total_amount'));
    }
}
