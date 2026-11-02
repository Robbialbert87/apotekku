<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Izin bawaan untuk peran Karyawan. Admin selalu memiliki akses penuh
     * melalui Gate::before sehingga tidak bergantung pada daftar ini.
     *
     * @var array<int, string>
     */
    public const KARYAWAN_PERMISSIONS = [
        'dashboard.view',
        'supplier.view',
        'supplier.create',
        'supplier.update',
        'supplier.deactivate',
        'invoice.view',
        'invoice.create',
        'invoice.update',
        'payment.view',
        'payment.create',
        'plan.view',
        'report.view',
        'report.export',
    ];

    public function run(): void
    {
        $admin = Role::query()->updateOrCreate(
            ['slug' => Role::ADMIN_SLUG],
            [
                'name' => 'Admin',
                'description' => 'Akses penuh ke seluruh aplikasi dan pengaturan sistem.',
                'is_system' => true,
            ],
        );

        $karyawan = Role::query()->updateOrCreate(
            ['slug' => 'karyawan'],
            [
                'name' => 'Karyawan',
                'description' => 'Operator harian untuk supplier, invoice, pembayaran, dan laporan.',
                'is_system' => true,
            ],
        );

        $admin->permissions()->sync(Permission::query()->pluck('id')->all());
        $karyawan->permissions()->sync(
            Permission::query()->whereIn('slug', self::KARYAWAN_PERMISSIONS)->pluck('id')->all(),
        );
    }
}
