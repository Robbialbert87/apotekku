<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Models\Payment;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'payment_number' => 'BYR-'.date('Ymd', $date->getTimestamp()).'-'.fake()->unique()->numerify('####'),
            'supplier_id' => Supplier::factory(),
            'payment_date' => $date,
            'total_amount' => fake()->numberBetween(100_000, 10_000_000),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'reference_number' => fake()->optional()->bothify('TRX-####-????'),
            'notes' => fake()->optional()->sentence(),
            'status' => PaymentRecordStatus::Posted,
            'created_by' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentRecordStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => 'Dibatalkan untuk pengujian.',
        ]);
    }
}
