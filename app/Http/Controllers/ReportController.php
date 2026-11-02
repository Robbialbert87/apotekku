<?php

namespace App\Http\Controllers;

use App\Enums\DueStatus;
use App\Enums\InvoicePaymentStatus;
use App\Models\Supplier;
use App\Services\ReportService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index(Request $request): View
    {
        $type = $this->type($request);
        $filters = $this->filters($request);

        $records = $this->reportService->paginate($type, $filters, 20);
        $summaryQuery = $this->reportService->query($type, $filters)->reorder()->get();

        $summary = match ($type) {
            'payments' => ['label' => 'Total Pembayaran', 'value' => $summaryQuery->sum(fn ($row): int => Money::toCents($row->total_amount))],
            'suppliers' => ['label' => 'Total Sisa Tagihan', 'value' => $summaryQuery->sum(fn ($row): int => Money::toCents($row->outstanding_total ?? 0))],
            default => ['label' => 'Total Sisa Tagihan', 'value' => $summaryQuery->sum(fn ($row): int => Money::toCents($row->remaining_amount))],
        };

        return view('reports.index', [
            'type' => $type,
            'types' => ReportService::TYPES,
            'records' => $records,
            'filters' => $filters,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'paymentStatuses' => InvoicePaymentStatus::options(),
            'dueStatuses' => DueStatus::options(),
            'summary' => [
                'label' => $summary['label'],
                'value' => Money::fromCents($summary['value']),
                'count' => $summaryQuery->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $type = $this->type($request);
        $filters = $this->filters($request);
        $export = $this->reportService->export($type, $filters);

        $filename = 'laporan-'.Str::slug(str_replace('Laporan ', '', ReportService::TYPES[$type])).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($export): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['No', ...$export['headings']]);

            $number = 1;

            foreach ($export['rows'] as $row) {
                fputcsv($handle, [$number++, ...$row]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function type(Request $request): string
    {
        $type = $request->string('type')->toString();

        return array_key_exists($type, ReportService::TYPES) ? $type : 'invoices';
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'supplier_id' => $request->input('supplier_id'),
            'payment_status' => $request->input('payment_status'),
            'due_status' => $request->input('due_status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'record_status' => $request->input('record_status', 'posted'),
        ];
    }
}
