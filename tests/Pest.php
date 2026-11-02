<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

// Helper to create an admin user.
function createAdmin(): User
{
    $role = Role::query()->where('slug', Role::ADMIN_SLUG)->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
    ]);
}

function createKaryawan(): User
{
    $role = Role::query()->where('slug', 'karyawan')->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
    ]);
}
