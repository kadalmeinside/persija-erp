<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserKaryawanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Buat Departemen default
            $mgmtDept = Departemen::firstOrCreate(
                ['nama_departemen' => 'MANAJEMEN']
            );
            
            $edpDept = Departemen::firstOrCreate(
                ['nama_departemen' => 'EDP'] // Dept untuk testing
            );

            // 2. Buat Admin User
            $adminUser = User::firstOrCreate(
                ['email' => 'admin@persija.id'],
                [
                    'name' => 'Admin ERP',
                    'password' => Hash::make('password') // Ganti password ini!
                ]
            );

            // 3. Buat data Karyawan untuk Admin User
            Karyawan::firstOrCreate(
                ['nomor_induk_karyawan' => 'ERP-001'],
                [
                    'user_id' => $adminUser->id,
                    'id_departemen' => $mgmtDept->id,
                    'nama_lengkap' => 'Admin ERP',
                    'jabatan' => 'Administrator',
                    'gaji_pokok' => 10000000,
                ]
            );

            // 4. Buat Staf User (UNTUK LOGIN STAF)
            $stafUser = User::firstOrCreate(
                ['email' => 'staf.edp@persija.id'],
                [
                    'name' => 'Staf EDP',
                    'password' => Hash::make('password') // Ganti password ini!
                ]
            );

            // 5. Buat data Karyawan untuk Staf User
            Karyawan::firstOrCreate(
                ['nomor_induk_karyawan' => 'EDP-001'],
                [
                    'user_id' => $stafUser->id, // Langsung hubungkan
                    'id_departemen' => $edpDept->id,
                    'nama_lengkap' => 'Staf EDP',
                    'jabatan' => 'Staf IT',
                    'gaji_pokok' => 7000000,
                ]
            );

            $this->command->info('User & Karyawan sampel berhasil dibuat.');
        });
    }
}