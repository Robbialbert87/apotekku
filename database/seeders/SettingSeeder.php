<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'apotek_name' => config('apotek.name', 'Apotek Billing'),
            'apotek_address' => '',
            'apotek_phone' => '',
            'apotek_email' => '',
            'invoice_due_days' => '30',
            'report_footer' => 'Dicetak dari Apotek Billing',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
