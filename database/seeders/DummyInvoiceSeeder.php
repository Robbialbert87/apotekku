<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DummyInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            Supplier::factory()->create(['name' => 'Dummy Supplier A', 'is_active' => true]),
            Supplier::factory()->create(['name' => 'Dummy Supplier B', 'is_active' => true]),
        ];

        for ($i = 1; $i <= 15; $i++) {
            $supplier = $suppliers[($i - 1) % 2];

            Invoice::factory()->create([
                'supplier_id' => $supplier->id,
                'invoice_number' => 'DUMMY-INV-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'created_by' => null,
            ]);
        }
    }
}