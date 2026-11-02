<?php

namespace App\Http\Controllers;

use App\Enums\DueStatus;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentPlanController extends Controller
{
    public function index(Request $request): View
    {
        $mode = $request->string('mode')->toString() === 'b' ? 'b' : 'a';

        $query = Invoice::query()
            ->outstanding()
            ->with('supplier')
            ->withPaidAmount()
            ->forSupplier($request->input('supplier_id'));

        if ($mode === 'a') {
            $dateFrom = $request->input('date_from') ?: today()->toDateString();
            $dateTo = $request->input('date_to') ?: today()->addDays(30)->toDateString();

            $query->dueBetween($dateFrom, $dateTo);
        } else {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            $query
                ->wherePaymentStatus($request->input('payment_status'))
                ->whereDueStatus($request->input('due_status'))
                ->dueBetween($dateFrom, $dateTo);
        }

        $invoices = $query
            ->orderBy('invoices.due_date')
            ->paginate((int) config('apotek.pagination', 10))
            ->withQueryString();

        $summaryQuery = $query->reorder()->get();

        return view('plans.index', [
            'mode' => $mode,
            'invoices' => $invoices,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'dueStatuses' => DueStatus::options(),
            'filters' => array_merge(
                $request->only(['supplier_id', 'payment_status', 'due_status', 'date_from', 'date_to']),
                [
                    'date_from' => $request->input('date_from') ?: ($mode === 'a' ? today()->toDateString() : null),
                    'date_to' => $request->input('date_to') ?: ($mode === 'a' ? today()->addDays(30)->toDateString() : null),
                ],
            ),
            'summary' => [
                'count' => $summaryQuery->count(),
                'total' => Money::fromCents($summaryQuery->sum(fn (Invoice $invoice): int => Money::toCents($invoice->remaining_amount))),
            ],
        ]);
    }
}
