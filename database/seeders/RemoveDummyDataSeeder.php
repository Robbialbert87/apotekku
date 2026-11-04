<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class RemoveDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        Invoice::query()
            ->where('invoice_number', 'like', 'DUMMY-%')
            ->delete();

        Supplier::query()
            ->where('name', 'like', 'Dummy Supplier%')
            ->delete();
    }
}