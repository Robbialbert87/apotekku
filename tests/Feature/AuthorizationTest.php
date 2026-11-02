<?php

test('inactive user cannot login', function (): void {
    $user = createKaryawan();
    $user->update(['is_active' => false]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('karyawan cannot access admin-only menus', function (): void {
    $user = createKaryawan();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('roles.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('settings.edit'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('activity.index'))
        ->assertForbidden();
});

test('admin can access all core pages', function (): void {
    $admin = createAdmin();

    foreach ([
        route('dashboard'),
        route('suppliers.index'),
        route('invoices.index'),
        route('payments.index'),
        route('plans.index'),
        route('reports.index'),
        route('users.index'),
        route('roles.index'),
        route('settings.edit'),
        route('activity.index'),
    ] as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }
});

test('guest is redirected to login', function (): void {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
