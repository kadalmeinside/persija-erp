<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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
            'permissions.manage',
            'settings.manage'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        $this->command->info('Permissions created.');

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $manajerRole = Role::firstOrCreate(['name' => 'Manajer Departemen']);
        $financeRole = Role::firstOrCreate(['name' => 'Staf Finance']);
        $stafRole = Role::firstOrCreate(['name' => 'Staf']);
        
        $this->command->info('Roles created.');

        
        $stafRole->givePermissionTo([
            'pengajuan.create',
            'pengajuan.view.own',
        ]);

        $manajerRole->givePermissionTo([
            'pengajuan.create',
            'pengajuan.view.own',
            'pengajuan.view.department',
            'pengajuan.approve.level1',
            'pengajuan.reject',
            'budget.view.department',
        ]);

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

        $adminRole->givePermissionTo(Permission::all());

        $this->command->info('Permissions assigned to roles.');

        
        $adminUser = User::where('email', 'admin@persija.id')->first();
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
            $this->command->info('Super Admin role assigned to admin@persija.id.');
        }

        $stafUser = User::where('email', 'staf.edp@persija.id')->first();
        if ($stafUser) {
            $stafUser->assignRole($stafRole);
            $this->command->info('Staf role assigned to staf.edp@persija.id.');
        }
        
        $this->command->info('Roles assigned to users.');
    }
}