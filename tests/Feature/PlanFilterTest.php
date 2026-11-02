<?php

use App\Models\Invoice;
use App\Models\Supplier;

beforeEach(function (): void {
    $this->admin = createAdmin();
    $this->supplier = Supplier::factory()->create(['is_active' => true]);
});

test('plan filter A filters by due date range and outstanding', function (): void {
    $dueSoon = Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
        'due_date' => now()->addDays(5),
    ]);
    $far = Invoice::factory()->total(50000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
        'due_date' => now()->addDays(60),
    ]);

    $response = $this->actingAs($this->admin)->get(route('plans.index', [
        'mode' => 'a',
        'date_from' => now()->toDateString(),
        'date_to' => now()->addDays(10)->toDateString(),
    ]));

    $response->assertOk()->assertSee($dueSoon->invoice_number);
    $response->assertDontSee($far->invoice_number);
});

test('plan filter B shows all outstanding with supplier filter', function (): void {
    $other = Supplier::factory()->create(['is_active' => true]);

    $mine = Invoice::factory()->total(100000)->create([
        'supplier_id' => $this->supplier->id,
        'created_by' => $this->admin->id,
        'due_date' => now()->addDays(5),
    ]);
    $theirs = Invoice::factory()->total(50000)->create([
        'supplier_id' => $other->id,
        'created_by' => $this->admin->id,
        'due_date' => now()->addDays(5),
    ]);

    $response = $this->actingAs($this->admin)->get(route('plans.index', [
        'mode' => 'b',
        'supplier_id' => $this->supplier->id,
    ]));

    $response->assertOk()->assertSee($mine->invoice_number);
    $response->assertDontSee($theirs->invoice_number);
});
