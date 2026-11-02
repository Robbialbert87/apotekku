<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $invoiceDate = fake()->dateTimeBetween('-60 days', 'now');

        return [
            'supplier_id' => Supplier::factory(),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'invoice_date' => $invoiceDate,
            'received_date' => fake()->optional()->dateTimeBetween($invoiceDate, 'now'),
            'due_date' => (clone $invoiceDate)->modify('+30 days'),
            'total_amount' => fake()->numberBetween(250_000, 25_000_000),
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'invoice_date' => now()->subDays(45),
            'due_date' => now()->subDays(10),
        ]);
    }

    public function dueToday(): static
    {
        return $this->state(fn (): array => [
            'invoice_date' => now()->subDays(30),
            'due_date' => now(),
        ]);
    }

    public function dueSoon(): static
    {
        return $this->state(fn (): array => [
            'invoice_date' => now()->subDays(20),
            'due_date' => now()->addDays(3),
        ]);
    }

    public function notDue(): static
    {
        return $this->state(fn (): array => [
            'invoice_date' => now(),
            'due_date' => now()->addDays(45),
        ]);
    }

    public function total(int|float|string $amount): static
    {
        return $this->state(fn (): array => [
            'total_amount' => $amount,
        ]);
    }

    public function voided(): static
    {
        return $this->state(fn (): array => [
            'voided_at' => now(),
            'void_reason' => 'Dibatalkan untuk pengujian.',
        ]);
    }
}
