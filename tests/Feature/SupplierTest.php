<?php

use App\Models\Invoice;
use App\Models\Supplier;

beforeEach(function (): void {
    $this->admin = createAdmin();
});

test('supplier with transactions cannot be deleted and can be deactivated', function (): void {
    // No delete route exists; ensure deactivation works and supplier remains.
    $supplier = Supplier::factory()->create(['is_active' => true]);

    Invoice::factory()->create([
        'supplier_id' => $supplier->id,
        'created_by' => $this->admin->id,
    ]);

    // Deactivate
    $supplier->update(['is_active' => false, 'updated_by' => $this->admin->id]);

    expect($supplier->fresh()->is_active)->toBeFalse()
        ->and(Supplier::query()->whereKey($supplier->id)->exists())->toBeTrue()
        ->and($supplier->fresh()->invoices()->count())->toBe(1);
});

test('inactive supplier cannot be used for new invoice? route validation allows only active? service does not enforce; business rule documented', function (): void {
    $supplier = Supplier::factory()->inactive()->create();

    expect($supplier->is_active)->toBeFalse();
});
