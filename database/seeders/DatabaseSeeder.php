<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            //AdminUserSeeder::class,
            UserKaryawanSeeder::class,
            RoleAndPermissionSeeder::class, 
            DummyDataSeeder::class, // Moved up to ensure employees exist before leave seeding
            MasterDataBudgetSeeder::class,
            MasterDataFinanceSeeder::class, // Added Finance Seeder
            MasterDataTaxSeeder::class, // Added Tax Seeder
            MasterDataCutiSeeder::class,
            HariLiburSeeder::class,
        ]);
    }
}
