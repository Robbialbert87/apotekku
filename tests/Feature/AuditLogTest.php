<?php

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Services\InvoiceService;
use App\Services\PaymentService;

beforeEach(function (): void {
    $this->admin = createAdmin();
    $this->supplier = Supplier::factory()->create(['is_active' => true]);
});

test('invoice creation writes an audit log', function (): void {
    $this->actingAs($this->admin);
    $service = app(InvoiceService::class);
    $invoice = $service->create([
        'supplier_id' => $this->supplier->id,
        'invoice_number' => 'INV-AUDIT',
        'invoice_date' => now()->toDateString(),
        'received_date' => null,
        'due_date' => now()->addDays(30)->toDateString(),
        'total_amount' => 100000,
        'notes' => null,
        'items' => [],
    ], $this->admin);

    $log = ActivityLog::query()
        ->where('subject_type', $invoice->getMorphClass())
        ->where('subject_id', $invoice->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->action)->toBe('invoice.created')
        ->and($log->user_id)->toBe($this->admin->id);
});

test('payment cancellation writes an audit log', function (): void {
    $this->actingAs($this->admin);
    $invoice = Invoice::factory()->total(50000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    $payment = app(PaymentService::class)->create([
        'supplier_id' => $this->supplier->id,
        'payment_date' => now()->toDateString(),
        'total_amount' => 50000,
        'method' => 'tunai',
    ], [$invoice->id => 50000], $this->admin);

    app(PaymentService::class)->cancel($payment, $this->admin, 'Salah input');

    $log = ActivityLog::query()
        ->where('subject_type', $payment->getMorphClass())
        ->where('subject_id', $payment->id)
        ->where('action', 'payment.cancelled')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['reason'])->toBe('Salah input');
});
