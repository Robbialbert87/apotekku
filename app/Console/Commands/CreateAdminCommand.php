<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    protected $signature = 'apotek:create-admin
        {email : Alamat email admin}
        {--name= : Nama admin}
        {--password= : Kata sandi admin (minimal 8 karakter)}';

    protected $description = 'Membuat akun admin pertama atau mengubah akun menjadi admin';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = $this->option('password');

        if ($password === null) {
            $password = $this->secret('Kata sandi admin (minimal 8 karakter)');
        }

        $validator = Validator::make([
            'name' => $this->option('name') ?: 'Administrator',
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        $adminRole = Role::query()->where('slug', Role::ADMIN_SLUG)->firstOrFail();

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->fill([
            'name' => $this->option('name') ?: $user->name ?: 'Administrator',
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
        $user->save();

        $this->info("Admin {$user->email} berhasil disiapkan.");

        return self::SUCCESS;
    }
}
