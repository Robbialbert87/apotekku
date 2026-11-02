<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
            ),
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        foreach (self::KEYS as $key) {
            Setting::put($key, $request->input($key));
        }

        $this->activityLogger->record('setting.updated', null, 'Pengaturan aplikasi diperbarui.');

        return redirect()
            ->route('settings.edit')
            ->with('status', 'Pengaturan berhasil disimpan.');
    }
}
