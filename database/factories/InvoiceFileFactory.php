<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceFile>
 */
class InvoiceFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'path' => 'apotek/invoices/contoh.pdf',
            'original_name' => 'invoice.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 500_000),
            'uploaded_by' => null,
        ];
    }
}
