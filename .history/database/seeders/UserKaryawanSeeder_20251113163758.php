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
            $mgmtDept = Departemen::firstOrCreate(
                ['nama_departemen' => 'MANAJEMEN']
            );
            
            $edpDept = Departemen::firstOrCreate(
                ['nama_departemen' => 'EDP'] /
            );

            $adminUser = User::firstOrCreate(
                ['email' => 'admin@persija.id'],
                [
                    'name' => 'Admin ERP',
                    'username' => 'admin123',
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                ]
            );

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

            $stafUser = User::firstOrCreate(
                ['email' => 'staf.edp@persija.id'],
                [
                    'name' => 'Staf EDP',
                    'password' => Hash::make('password') 
                ]
            );

            Karyawan::firstOrCreate(
                ['nomor_induk_karyawan' => 'EDP-001'],
                [
                    'user_id' => $stafUser->id, 
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