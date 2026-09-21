<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Super Admin')->first();

        if (!$adminRole) {
            $this->command->error('Role "Super Admin" tidak ditemukan. Jalankan RoleAndPermissionSeeder terlebih dahulu.');
            return;
        }

        // Create Admin User
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@persija.id'], 
            [
                'name' => 'Admin ERP', 
                'username' => 'admin123',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $adminUser->assignRole($adminRole);

        // Get Default Department
        $dept = Departemen::where('nama_departemen', 'IT')->first();

        // Create Karyawan Profile for Admin
        Karyawan::firstOrCreate(
            ['user_id' => $adminUser->id],
            [
                'id_departemen' => $dept ? $dept->id : 1,
                'nomor_induk_karyawan' => 'ERP-001',
                'nama_lengkap' => 'Admin ERP',
                'jenis_kelamin' => 'L',
                'jabatan' => 'Administrator',
                'status_karyawan' => 'Tetap',
                'gaji_pokok' => 10000000,
                'status_ptkp' => 'TK/0'
            ]
        );

        $this->command->info('Admin user dan Karyawan profile berhasil dibuat.');
    }
}
