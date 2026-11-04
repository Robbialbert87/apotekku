<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        $expenseYear = (int) ($request->input('expense_year') ?: today()->format('Y'));
        $expenseMonthNumber = (int) ($request->input('expense_month') ?: today()->format('n'));

        if ($expenseMonthNumber < 1 || $expenseMonthNumber > 12) {
            $expenseMonthNumber = (int) today()->format('n');
        }

        if ($expenseYear < 2000 || $expenseYear > (int) today()->format('Y') + 1) {
            $expenseYear = (int) today()->format('Y');
        }

        $expenseMonth = sprintf('%04d-%02d', $expenseYear, $expenseMonthNumber);

        $expenseStart = \Carbon\Carbon::createFromFormat('Y-m', $expenseMonth)->startOfMonth();
        $expenseEnd = (clone $expenseStart)->endOfMonth();

        $expenseYears = range((int) today()->format('Y') - 4, (int) today()->format('Y'));

        $expenseMonthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('dashboard', [
            'metrics' => $this->dashboard->metrics(),
            'monthlyChart' => $this->dashboard->monthlyChart(),
            'statusChart' => $this->dashboard->paymentStatusChart(),
            'topSuppliers' => $this->dashboard->topSuppliers(),
            'latestInvoices' => $this->dashboard->latestInvoices(),
            'nearestDue' => $this->dashboard->nearestDue(),
            'overdueInvoices' => $this->dashboard->overdueInvoices(),
            'expenseYear' => $expenseYear,
            'expenseMonthNumber' => $expenseMonthNumber,
            'expenseMonth' => $expenseMonth,
            'monthlyExpense' => Money::fromCents($this->dashboard->monthlyPaymentTotal($expenseMonth)),
            'expenseYears' => $expenseYears,
            'expenseMonthNames' => $expenseMonthNames,
            'expenseStart' => $expenseStart->toDateString(),
            'expenseEnd' => $expenseEnd->toDateString(),
        ]);
    }
}
