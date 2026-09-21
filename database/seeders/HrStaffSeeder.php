<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Hash;

class HrStaffSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Role
        $role = Role::firstOrCreate(['name' => 'HR Staff']);
        $this->command->info('Role "HR Staff" created/verified.');

        // 2. Create Permissions if not exist
        $permissions = [
            'karyawan.manage',
            'payroll.run',
            'payroll.view',
            'hari-libur.manage', // Ensure this exists
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName]);
        }

        // 3. Assign Permissions to Role
        $role->syncPermissions($permissions);
        $this->command->info('Permissions assigned to "HR Staff".');

        // 4. Create User
        $user = User::firstOrCreate(
            ['email' => 'hr.staff@persija.id'],
            [
                'name' => 'Staf HR',
                'password' => Hash::make('password'),
            ]
        );

        // 5. Assign Role to User
        $user->assignRole($role);
        $this->command->info('User "hr.staff@persija.id" created and assigned "HR Staff" role.');

        // 6. Create Dummy Karyawan Data for this user (so they can use ESS features)
        if (!$user->karyawan) {
            $dept = \App\Models\Departemen::first();
            Karyawan::create([
                'user_id' => $user->id,
                'id_departemen' => $dept ? $dept->id : null,
                'nama_lengkap' => 'Staf HR',
                'nomor_induk_karyawan' => 'HR001',
                'jabatan' => 'HR Staff',
                'status_karyawan' => 'Tetap',
                'tgl_bergabung' => now(),
                'gaji_pokok' => 5000000,
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Jakarta',
                'tgl_lahir' => '1990-01-01',
                'alamat' => 'Jakarta',
            ]);
            $this->command->info('Dummy Karyawan data created for HR Staff.');
        }
    }
}
