<?php

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->admin = createAdmin();
});

test('user can view settings page with password form', function (): void {
    $this->actingAs($this->admin)
        ->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('Keamanan Akun')
        ->assertSee('Ganti Kata Sandi');
});

test('user can change password with valid credentials', function (): void {
    $response = $this->actingAs($this->admin)
        ->put(route('settings.password'), [
            'current_password' => 'password',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

    $response->assertRedirect(route('settings.edit'))
        ->assertSessionHas('status', 'Kata sandi berhasil diperbarui.');

    expect(Hash::check('secret12345', $this->admin->fresh()->password))->toBeTrue();

    $log = ActivityLog::query()
        ->where('action', 'user.password_updated')
        ->where('user_id', $this->admin->id)
        ->first();

    expect($log)->not->toBeNull();
});

test('user cannot change password with incorrect current password', function (): void {
    $response = $this->actingAs($this->admin)
        ->put(route('settings.password'), [
            'current_password' => 'wrong-password',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

    $response->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $this->admin->fresh()->password))->toBeTrue();
});

test('user cannot change password when confirmation does not match', function (): void {
    $response = $this->actingAs($this->admin)
        ->put(route('settings.password'), [
            'current_password' => 'password',
            'password' => 'secret12345',
            'password_confirmation' => 'different12345',
        ]);

    $response->assertSessionHasErrors('password');

    expect(Hash::check('password', $this->admin->fresh()->password))->toBeTrue();
});

test('user cannot change password when new password is too short', function (): void {
    $response = $this->actingAs($this->admin)
        ->put(route('settings.password'), [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

    $response->assertSessionHasErrors('password');

    expect(Hash::check('password', $this->admin->fresh()->password))->toBeTrue();
});
