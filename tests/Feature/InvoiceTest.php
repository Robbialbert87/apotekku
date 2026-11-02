<?php

use App\Enums\DueStatus;
use App\Enums\InvoicePaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Supplier;
use App\Services\InvoiceService;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->admin = createAdmin();
    $this->supplier = Supplier::factory()->create(['is_active' => true]);
});

test('invoice number is unique per supplier', function (): void {
    Invoice::factory()->create([
        'supplier_id' => $this->supplier->id,
        'invoice_number' => 'INV-001',
        'created_by' => $this->admin->id,
    ]);

    $this->expectException(QueryException::class);

    Invoice::factory()->create([
        'supplier_id' => $this->supplier->id,
        'invoice_number' => 'INV-001',
        'created_by' => $this->admin->id,
    ]);
});

test('invoice payment status is computed', function (): void {
    $invoice = Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    expect($invoice->payment_status)->toBe(InvoicePaymentStatus::BelumDibayar);

    // partial payment via factory allocation
    $payment = Payment::factory()->create([
        'supplier_id' => $this->supplier->id,
        'total_amount' => 40000,
        'created_by' => $this->admin->id,
    ]);
    PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
        'amount' => 40000,
    ]);

    $invoice->refresh();
    expect($invoice->paid_amount)->toBe('40000.00')
        ->and($invoice->remaining_amount)->toBe('60000.00')
        ->and($invoice->payment_status)->toBe(InvoicePaymentStatus::DibayarSebagian);

    $payment2 = Payment::factory()->create([
        'supplier_id' => $this->supplier->id,
        'total_amount' => 60000,
        'created_by' => $this->admin->id,
    ]);
    PaymentAllocation::factory()->create([
        'payment_id' => $payment2->id,
        'invoice_id' => $invoice->id,
        'amount' => 60000,
    ]);

    $invoice->refresh();
    expect($invoice->payment_status)->toBe(InvoicePaymentStatus::Lunas)
        ->and($invoice->isPaid())->toBeTrue();
});

test('invoice due status is computed', function (): void {
    $overdue = Invoice::factory()->overdue()->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);
    $today = Invoice::factory()->dueToday()->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);
    $soon = Invoice::factory()->dueSoon()->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);
    $notDue = Invoice::factory()->notDue()->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    expect($overdue->due_status)->toBe(DueStatus::Terlambat)
        ->and($today->due_status)->toBe(DueStatus::JatuhTempoHariIni)
        ->and($soon->due_status)->toBe(DueStatus::SegeraJatuhTempo)
        ->and($notDue->due_status)->toBe(DueStatus::BelumJatuhTempo);
});

test('invoice total is locked once payments exist', function (): void {
    $invoice = Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    $payment = Payment::factory()->create([
        'supplier_id' => $this->supplier->id,
        'total_amount' => 30000,
        'created_by' => $this->admin->id,
    ]);
    PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
        'amount' => 30000,
    ]);

    $service = app(InvoiceService::class);

    try {
        $service->update($invoice, [
            'supplier_id' => $this->supplier->id,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date->toDateString(),
            'received_date' => null,
            'due_date' => $invoice->due_date->toDateString(),
            'total_amount' => 120000,
            'notes' => $invoice->notes,
            'items' => [],
        ], $this->admin);
        $this->fail('Expected validation exception was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('total_amount');
    }
});

test('invoice can be voided only without payments', function (): void {
    $invoice = Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);

    $service = app(InvoiceService::class);
    $voided = $service->void($invoice, $this->admin, 'Salah input');

    expect($voided->isVoided())->toBeTrue()
        ->and($voided->void_reason)->toBe('Salah input');

    $invoice2 = Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
    ]);
    $payment = Payment::factory()->create([
        'supplier_id' => $this->supplier->id,
        'total_amount' => 10000,
        'created_by' => $this->admin->id,
    ]);
    PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'invoice_id' => $invoice2->id,
        'amount' => 10000,
    ]);

    try {
        $service->void($invoice2, $this->admin, 'Coba');
        $this->fail('Expected validation exception was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('invoice');
    }
});
