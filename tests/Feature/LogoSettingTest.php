<?php

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->admin = createAdmin();
});

test('logo dapat diunggah dan tampil di sidebar', function (): void {
    Storage::fake(config('apotek.upload.disk', 'local'));

    $response = $this->actingAs($this->admin)->put(route('settings.update'), [
        'apotek_name' => 'Apotek Uji',
        'apotek_address' => '',
        'apotek_phone' => '',
        'apotek_email' => '',
        'invoice_due_days' => 30,
        'report_footer' => '',
        'apotek_logo' => UploadedFile::fake()->image('logo.png', 64, 64),
    ]);

    $response->assertRedirect(route('settings.edit'));

    $path = Setting::get('apotek_logo');
    expect($path)->not->toBeNull();
    Storage::disk(config('apotek.upload.disk', 'local'))->assertExists($path);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('settings.logo'));
});

test('logo dapat dihapus', function (): void {
    Storage::fake(config('apotek.upload.disk', 'local'));

    Setting::put('apotek_logo', 'apotek/logo/lama.png');
    Storage::disk(config('apotek.upload.disk', 'local'))->put('apotek/logo/lama.png', 'dummy');

    $response = $this->actingAs($this->admin)->put(route('settings.update'), [
        'apotek_name' => 'Apotek Uji',
        'apotek_address' => '',
        'apotek_phone' => '',
        'apotek_email' => '',
        'invoice_due_days' => 30,
        'report_footer' => '',
        'remove_logo' => 1,
    ]);

    $response->assertRedirect(route('settings.edit'));

    expect(Setting::get('apotek_logo'))->toBeNull();
    Storage::disk(config('apotek.upload.disk', 'local'))->assertMissing('apotek/logo/lama.png');
});
