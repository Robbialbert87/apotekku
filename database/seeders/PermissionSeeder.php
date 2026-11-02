<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Daftar izin bawaan aplikasi. Aman dijalankan berulang kali.
     *
     * @var array<string, array<string, string>>
     */
    public const PERMISSIONS = [
        'Dashboard' => [
            'dashboard.view' => 'Melihat dashboard',
        ],
        'Supplier' => [
            'supplier.view' => 'Melihat supplier',
            'supplier.create' => 'Menambah supplier',
            'supplier.update' => 'Mengubah supplier',
            'supplier.deactivate' => 'Menonaktifkan supplier',
        ],
        'Invoice' => [
            'invoice.view' => 'Melihat invoice',
            'invoice.create' => 'Menambah invoice',
            'invoice.update' => 'Mengubah invoice',
            'invoice.void' => 'Membatalkan invoice',
        ],
        'Pembayaran' => [
            'payment.view' => 'Melihat pembayaran',
            'payment.create' => 'Mencatat pembayaran',
            'payment.cancel' => 'Membatalkan pembayaran',
        ],
        'Rencana Pembayaran' => [
            'plan.view' => 'Melihat rencana pembayaran',
        ],
        'Laporan' => [
            'report.view' => 'Melihat laporan',
            'report.export' => 'Mengekspor laporan',
        ],
        'Pengguna' => [
            'user.view' => 'Melihat pengguna',
            'user.create' => 'Menambah pengguna',
            'user.update' => 'Mengubah pengguna',
            'user.deactivate' => 'Menonaktifkan pengguna',
        ],
        'Peran' => [
            'role.view' => 'Melihat peran',
            'role.update' => 'Mengubah hak akses peran',
        ],
        'Pengaturan' => [
            'setting.view' => 'Melihat pengaturan',
            'setting.update' => 'Mengubah pengaturan',
        ],
        'Log Aktivitas' => [
            'activity.view' => 'Melihat log aktivitas',
        ],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $group => $permissions) {
            foreach ($permissions as $slug => $name) {
                Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'group' => $group],
                );
            }
        }
    }
}
