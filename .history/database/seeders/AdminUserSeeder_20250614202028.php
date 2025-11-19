<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari role 'admin', pastikan sudah ada dari RoleAndPermissionSeeder
        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) {
            $this->command->error('Role "admin" tidak ditemukan. Jalankan RoleAndPermissionSeeder terlebih dahulu.');
            return;
        }

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@video.com'], 
            [
                'name' => 'Admin', 
                'username' => 'admin123',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        $adminUser->assignRole($adminRole);

        $this->command->info('Admin user "admin@video.com" berhasil dibuat dan diberi role admin.');
    }
}
