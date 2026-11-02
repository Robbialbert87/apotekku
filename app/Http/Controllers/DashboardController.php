<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(): View
    {
        return view('dashboard', [
            'metrics' => $this->dashboard->metrics(),
            'monthlyChart' => $this->dashboard->monthlyChart(),
            'statusChart' => $this->dashboard->paymentStatusChart(),
            'topSuppliers' => $this->dashboard->topSuppliers(),
            'latestInvoices' => $this->dashboard->latestInvoices(),
            'nearestDue' => $this->dashboard->nearestDue(),
            'overdueInvoices' => $this->dashboard->overdueInvoices(),
        ]);
    }
}
