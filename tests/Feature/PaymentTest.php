<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use App\Services\PaymentService;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->admin = createAdmin();
    $this->supplier = Supplier::factory()->create(['is_active' => true]);
});

test('payment prevents overpayment', function (): void {
    $invoice = Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    $service = app(PaymentService::class);

    try {
        $service->create([
            'supplier_id' => $this->supplier->id,
            'payment_date' => now()->toDateString(),
            'total_amount' => 120000,
            'method' => 'transfer',
        ], [$invoice->id => 120000], $this->admin);
        $this->fail('Expected validation exception was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('allocations');
    }
});

test('payment prevents over-allocation to a single invoice', function (): void {
    $invoice = Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    $service = app(PaymentService::class);

    try {
        $service->create([
            'supplier_id' => $this->supplier->id,
            'payment_date' => now()->toDateString(),
            'total_amount' => 100000,
            'method' => 'transfer',
        ], [$invoice->id => 100000], $this->admin);
        // Now invoice fully paid; second payment should fail
        $service->create([
            'supplier_id' => $this->supplier->id,
            'payment_date' => now()->toDateString(),
            'total_amount' => 10000,
            'method' => 'transfer',
        ], [$invoice->id => 10000], $this->admin);
        $this->fail('Expected validation exception was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('allocations');
    }
});

test('payment requires full allocation of total', function (): void {
    $invoice = Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    $service = app(PaymentService::class);

    try {
        $service->create([
            'supplier_id' => $this->supplier->id,
            'payment_date' => now()->toDateString(),
            'total_amount' => 100000,
            'method' => 'transfer',
        ], [$invoice->id => 90000], $this->admin);
        $this->fail('Expected validation exception was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('allocations');
    }
});

test('one payment can pay multiple invoices from same supplier', function (): void {
    $invoiceA = Invoice::factory()->total(60000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);
    $invoiceB = Invoice::factory()->total(40000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    $service = app(PaymentService::class);
    $payment = $service->create([
        'supplier_id' => $this->supplier->id,
        'payment_date' => now()->toDateString(),
        'total_amount' => 100000,
        'method' => 'transfer',
    ], [
        $invoiceA->id => 60000,
        $invoiceB->id => 40000,
    ], $this->admin);

    expect($payment->allocations)->toHaveCount(2)
        ->and($invoiceA->fresh()->isPaid())->toBeTrue()
        ->and($invoiceB->fresh()->isPaid())->toBeTrue();
});

test('payment to different supplier invoice is rejected', function (): void {
    $otherSupplier = Supplier::factory()->create(['is_active' => true]);
    $invoice = Invoice::factory()->total(50000)->create([
        'supplier_id' => $otherSupplier->id,
        'created_by' => $this->admin->id,
    ]);

    $service = app(PaymentService::class);

    try {
        $service->create([
            'supplier_id' => $this->supplier->id,
            'payment_date' => now()->toDateString(),
            'total_amount' => 50000,
            'method' => 'transfer',
        ], [$invoice->id => 50000], $this->admin);
        $this->fail('Expected validation exception was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('allocations');
    }
});

test('cancelling payment restores invoice balance', function (): void {
    $invoice = Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    $service = app(PaymentService::class);
    $payment = $service->create([
        'supplier_id' => $this->supplier->id,
        'payment_date' => now()->toDateString(),
        'total_amount' => 100000,
        'method' => 'transfer',
    ], [$invoice->id => 100000], $this->admin);

    expect($invoice->fresh()->isPaid())->toBeTrue();

    $service->cancel($payment, $this->admin, 'Transfer gagal');

    expect($payment->fresh()->isCancelled())->toBeTrue()
        ->and($invoice->fresh()->payment_status->value)->toBe('unpaid')
        ->and($invoice->fresh()->remaining_amount)->toBe('100000.00');
});

test('payment number is generated sequentially', function (): void {
    $invoice = Invoice::factory()->total(50000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    $service = app(PaymentService::class);
    $p1 = $service->create([
        'supplier_id' => $this->supplier->id,
        'payment_date' => now()->toDateString(),
        'total_amount' => 50000,
        'method' => 'tunai',
    ], [$invoice->id => 50000], $this->admin);

    $invoice2 = Invoice::factory()->total(30000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);
    $p2 = $service->create([
        'supplier_id' => $this->supplier->id,
        'payment_date' => now()->toDateString(),
        'total_amount' => 30000,
        'method' => 'tunai',
    ], [$invoice2->id => 30000], $this->admin);

    expect($p1->payment_number)->toMatch('/^BYR-\d{8}-0001$/')
        ->and($p2->payment_number)->toMatch('/^BYR-\d{8}-0002$/');
});
