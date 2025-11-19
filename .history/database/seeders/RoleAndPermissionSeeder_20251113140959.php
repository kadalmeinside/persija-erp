<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Karyawan;
use IlluminateDatabase\Console\Seeds\WithoutModelEvents;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Bersihkan cache permission
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Buat Permissions (Izin Granular)
        $permissions = [
            // Pengajuan
            'pengajuan.create',
            'pengajuan.view.own',
            'pengajuan.view.department',
            'pengajuan.view.all',
            'pengajuan.approve.level1',
            'pengajuan.approve.level2',
            'pengajuan.pay',
            'pengajuan.reject',
            
            // Anggaran
            'budget.view.department',
            'budget.view.all',
            'budget.manage', // Untuk import/update

            // Laporan
            'report.view.gl',
            'report.view.ap',
            'report.view.ar',

            // Master Data
            'master.manage.all',

            // HRIS & Payroll
            'payroll.run',
            'payroll.view',
            'karyawan.manage',

            // AR & PO
            'fakturjual.manage',
            'po.manage',

            // Pengaturan Sistem
            'user.manage',
            'role.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        $this->command->info('Permissions created.');

        // 3. Buat Roles
        $adminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $manajerRole = Role::firstOrCreate(['name' => 'Manajer Departemen']);
        $financeRole = Role::firstOrCreate(['name' => 'Staf Finance']);
        $stafRole = Role::firstOrCreate(['name' => 'Staf']);
        
        $this->command->info('Roles created.');

        // 4. Tugaskan Permissions ke Roles
        
        // Staf: Hanya bisa membuat pengajuan & melihat miliknya
        $stafRole->givePermissionTo([
            'pengajuan.create',
            'pengajuan.view.own',
        ]);

        // Manajer: Bisa membuat, melihat dept, dan approve level 1
        $manajerRole->givePermissionTo([
            'pengajuan.create',
            'pengajuan.view.own',
            'pengajuan.view.department',
            'pengajuan.approve.level1',
            'pengajuan.reject',
            'budget.view.department',
        ]);

        // Finance: Bisa melihat semua, membayar, dan mengelola budget/laporan
        $financeRole->givePermissionTo([
            'pengajuan.view.all',
            'pengajuan.pay',
            'budget.view.all',
            'budget.manage',
            'report.view.gl',
            'report.view.ap',
            'report.view.ar',
            'payroll.run',
            'payroll.view',
            'fakturjual.manage',
            'po.manage',
        ]);

        // Super Admin: Bisa melakukan segalanya
        $adminRole->givePermissionTo(Permission::all());

        $this->command->info('Permissions assigned to roles.');

        // 5. Tugaskan Roles ke User Sampel (dari UserKaryawanSeeder)
        
        // Tugaskan 'Super Admin' ke admin@persija.id
        $adminUser = User::where('email', 'admin@persija.id')->first();
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
            $this->command->info('Super Admin role assigned to admin@persija.id.');
        }

        // Buat login untuk Staf EDP dan tugaskan role 'Staf'
        $stafKaryawan = Karyawan::where('nomor_induk_karyawan', 'EDP-001')->first();
        if ($stafKaryawan) {
            $stafUser = User::firstOrCreate(
                ['email' => 'staf.edp@persija.id'],
                [
                    'name' => $stafKaryawan->nama_lengkap,
                    'password' => Hash::make('password') // Ganti password ini!
                ]
            );
            
            // Hubungkan User ke Karyawan
            $stafKaryawan->user_id = $stafUser->id;
            $stafKaryawan->save();
            
            // Tugaskan role
            $stafUser->assignRole($stafRole);
            $this.command->info('Staf role assigned to staf.edp@persija.id.');
        }
        
        $this->command->info('Roles assigned to users.');
    }
}