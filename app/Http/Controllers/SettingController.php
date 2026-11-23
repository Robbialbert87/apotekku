<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SettingController extends Controller
{
    private const KEYS = [
        'apotek_name',
        'apotek_address',
        'apotek_phone',
        'apotek_email',
        'invoice_due_days',
        'report_footer',
    ];

    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function edit(): View
    {
        return view('settings.edit', [
            'settings' => collect(self::KEYS)->mapWithKeys(
                fn (string $key): array => [$key => Setting::get($key)],
            )->put('apotek_logo', Setting::get('apotek_logo')),
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        foreach (self::KEYS as $key) {
            Setting::put($key, $request->input($key));
        }

        $this->syncLogo($request);

        $this->activityLogger->record('setting.updated', null, 'Pengaturan aplikasi diperbarui.');

        return redirect()
            ->route('settings.edit')
            ->with('status', 'Pengaturan berhasil disimpan.');
    }

    public function logo(): Response
    {
        $path = Setting::get('apotek_logo');

        abort_if($path === null || $path === '', 404);

        $disk = Storage::disk(config('apotek.upload.disk', 'local'));

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }

    private function syncLogo(UpdateSettingRequest $request): void
    {
        $disk = Storage::disk(config('apotek.upload.disk', 'local'));
        $current = Setting::get('apotek_logo');

        if ($request->boolean('remove_logo')) {
            if ($current !== null && $current !== '' && $disk->exists($current)) {
                $disk->delete($current);
            }

            Setting::put('apotek_logo', null);

            return;
        }

        if (! $request->hasFile('apotek_logo')) {
            return;
        }

        if ($current !== null && $current !== '' && $disk->exists($current)) {
            $disk->delete($current);
        }

        $path = $request->file('apotek_logo')->store('apotek/logo', config('apotek.upload.disk', 'local'));

        Setting::put('apotek_logo', $path);
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        $this->activityLogger->record(
            'user.password_updated',
            $user,
            'Kata sandi akun Anda berhasil diperbarui.',
        );

        return redirect()
            ->route('settings.edit')
            ->with('status', 'Kata sandi berhasil diperbarui.');
    }
}
