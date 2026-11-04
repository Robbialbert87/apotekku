<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()->where('slug', Role::ADMIN_SLUG)->first();
        if ($adminRole) {
            User::query()->updateOrCreate(
                ['email' => 'admin@apotek.test'],
                [
                    'name' => 'Administrator',
                    'password' => Hash::make('admin12345'),
                    'role_id' => $adminRole->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        $karyawanRole = Role::query()->where('slug', 'karyawan')->first();
        if ($karyawanRole) {
            User::query()->updateOrCreate(
                ['email' => 'karyawan@apotek.test'],
                [
                    'name' => 'Karyawan / Operator',
                    'password' => Hash::make('password'),
                    'role_id' => $karyawanRole->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
