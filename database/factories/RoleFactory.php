<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_system' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Admin',
            'slug' => Role::ADMIN_SLUG,
            'description' => 'Akses penuh ke seluruh aplikasi.',
            'is_system' => true,
        ]);
    }

    public function karyawan(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Karyawan',
            'slug' => 'karyawan',
            'description' => 'Operator harian apotek.',
            'is_system' => false,
        ]);
    }
}
