<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => [
            'role_id' => Role::query()->firstOrCreate(
                ['slug' => Role::ADMIN_SLUG],
                ['name' => 'Admin', 'description' => 'Akses penuh.', 'is_system' => true],
            )->id,
        ]);
    }

    public function karyawan(): static
    {
        return $this->state(fn (): array => [
            'role_id' => Role::query()->firstOrCreate(
                ['slug' => 'karyawan'],
                ['name' => 'Karyawan', 'description' => 'Operator harian.', 'is_system' => false],
            )->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => [
            'email_verified_at' => null,
        ]);
    }
}
