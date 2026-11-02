<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Supplier;
use App\Services\DashboardService;
use App\Services\ReportService;

beforeEach(function (): void {
    $this->admin = createAdmin();
    $this->supplier = Supplier::factory()->create(['is_active' => true]);
});

test('dashboard metrics reflect database data', function (): void {
    Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);
    Invoice::factory()->total(50000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    $metrics = app(DashboardService::class)->metrics();

    expect($metrics['invoices']['value'])->toBe(2)
        ->and($metrics['billed']['value'])->toBe('150000.00');
});

test('reports can be exported as csv', function (): void {
    Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
        'invoice_number' => 'INV-CSV-001',
    ]);

    $this->actingAs($this->admin)
        ->get(route('reports.export', ['type' => 'invoices']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $response = $this->actingAs($this->admin)
        ->get(route('reports.index', ['type' => 'invoices']));

    $response->assertOk()
        ->assertSee('INV-CSV-001');
});

test('report filters by payment status', function (): void {
    $paidInvoice = Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
        'invoice_number' => 'INV-PAID',
    ]);
    $unpaidInvoice = Invoice::factory()->total(50000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
        'invoice_number' => 'INV-UNPAID',
    ]);

    $payment = Payment::factory()->create([
        'supplier_id' => $this->supplier->id,
        'total_amount' => 100000,
        'created_by' => $this->admin->id,
    ]);
    PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'invoice_id' => $paidInvoice->id,
        'amount' => 100000,
    ]);

    $records = app(ReportService::class)->query('invoices', ['payment_status' => 'unpaid'])->get();

    expect($records->pluck('invoice_number')->all())->toContain('INV-UNPAID')
        ->and($records->pluck('invoice_number')->all())->not->toContain('INV-PAID');
});
