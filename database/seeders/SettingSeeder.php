<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'app_logo', 'value' => 'logos/FTY1DIGd3sSbsv90sAozYqXvo25AKl8Kp98dehFy.png'],
            ['key' => 'app_name', 'value' => 'PJH-ERP'],
            ['key' => 'company_address', 'value' => 'Jl. Rotan, Bojongsari Baru, Kec. Bojongsari, Kota Depok, Jawa Barat 16516'],
            ['key' => 'company_logo', 'value' => 'logos/eH5PXttn83SWOWDn977imKPm8eWwph18101HgsU5.png'],
            ['key' => 'company_name', 'value' => 'PERSIJA JAKARTA HEBAT'],
            ['key' => 'reimburse_tolerance_limit', 'value' => '1000000']
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }

        $this->command->info('Settings berhasil diseder.');
    }
}
