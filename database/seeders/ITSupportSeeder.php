<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\Jabatan; // Assuming Jabatan model exists or we use enum/string
use Illuminate\Support\Facades\Hash;

class ITSupportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Role
        $role = Role::firstOrCreate(['name' => 'IT Support', 'guard_name' => 'web']);
        
        // Give basic permissions if needed (e.g. view dashboard)
        // $role->givePermissionTo('view backend'); 

        // 2. Create Departemen IT if not exists
        $dept = Departemen::firstOrCreate(
            ['nama_departemen' => 'Information Technology'],
            // ['deskripsi' => 'IT Support'] // removed if column doesn't exist.
            [] // second arg empty or other fillables
        );

        // 3. Create User
        $user = User::firstOrCreate(
            ['email' => 'it.support@persija.id'],
            [
                'name' => 'IT Support Staff',
                'password' => Hash::make('password123'), // Default password
            ]
        );

        // 4. Assign Role
        if (!$user->hasRole('IT Support')) {
            $user->assignRole($role);
        }

        // 5. Create Karyawan Record linked to User
        if (!$user->karyawan) {
            Karyawan::create([
                'user_id' => $user->id,
                'nama_lengkap' => $user->name,
                'nomor_induk_karyawan' => 'IT-'.rand(100,999),
                // 'email' => $user->email, // Not in fillable/table likely
                // 'no_hp' => '08123456789', // Not in fillable
                'alamat' => 'Jakarta',
                'tgl_lahir' => '1995-01-01',
                'tgl_bergabung' => now(),
                'status_karyawan' => 'Tetap',
                'id_departemen' => $dept->id,
                'jabatan' => 'IT Staff', 
                'gaji_pokok' => 5000000,
            ]);
        }
        
        $this->command->info('IT Support Role, User, and Employee created successfully.');
    }
}
