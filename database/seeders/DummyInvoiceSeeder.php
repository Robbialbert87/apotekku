<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DummyInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        Invoice::query()
            ->where('invoice_number', 'like', 'DUMMY-%')
            ->delete();

        Supplier::query()
            ->where('name', 'like', 'Dummy Supplier%')
            ->delete();

        $suppliers = collect([
            'Dummy Supplier A',
            'Dummy Supplier B',
            'Dummy Supplier C',
        ])->map(fn (string $name): Supplier => Supplier::factory()->create([
            'name' => $name,
            'is_active' => true,
        ]));

        for ($i = 1; $i <= 40; $i++) {
            $factory = Invoice::factory();

            $factory = match (($i - 1) % 4) {
                0 => $factory->overdue(),
                1 => $factory->dueToday(),
                2 => $factory->dueSoon(),
                3 => $factory->notDue(),
            };

            $factory->create([
                'supplier_id' => $suppliers[($i - 1) % 3]->id,
                'invoice_number' => 'DUMMY-INV-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'created_by' => null,
            ]);
        }
    }
}
