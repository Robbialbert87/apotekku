<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'name' => fake()->randomElement([
                'Paracetamol 500 mg',
                'Amoxicillin 500 mg',
                'Vitamin C',
                'Ibuprofen 400 mg',
                'OBH Combi',
                'Antasida Doen',
                'Cetirizine 10 mg',
            ]),
            'quantity' => fake()->numberBetween(1, 50),
            'unit' => fake()->randomElement(['kotak', 'strip', 'botol', 'pack']),
            'sort_order' => 0,
        ];
    }
}
