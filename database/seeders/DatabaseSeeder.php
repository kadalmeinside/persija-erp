<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class, 
            DepartemenSeeder::class, // Seed base departments
            AdminUserSeeder::class, // Create Admin with Karyawan Profile
            SettingSeeder::class,   // Base App Settings
            MasterDataFinanceSeeder::class, // Base GL Accounts
            MasterDataTaxSeeder::class, // Base Taxes
            MasterDataCutiSeeder::class, // Base Cuti Types
            HariLiburSeeder::class // Base Public Holidays
        ]);
    }
}
