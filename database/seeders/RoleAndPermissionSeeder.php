<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // -------------------------------------------------------
        // PERMISSIONS
        // -------------------------------------------------------
        $permissions = [
            'pengajuan.create',
            'pengajuan.view.own',
            'pengajuan.view.department',
            'pengajuan.view.all',
            'pengajuan.approve.level1',
            'pengajuan.approve.level2',
            'pengajuan.pay',
            'pengajuan.reject',
            'budget.view.department',
            'budget.view.all',
            'budget.manage',
            'report.view.gl',
            'report.view.ap',
            'report.view.ar',
            'master.manage.all',
            'payroll.run',
            'payroll.view',
            'karyawan.manage',
            'fakturjual.manage',
            'po.manage',
            'user.manage',
            'role.manage',
            'permissions.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        $this->command->info('✅ Permissions created/verified.');

        // -------------------------------------------------------
        // ROLES — harus sesuai PERSIS dengan yang ada di routes/web.php
        // dan di hasRole() checks di controllers
        // -------------------------------------------------------
        $superAdminRole     = Role::firstOrCreate(['name' => 'Super Admin']);
        $manajerRole        = Role::firstOrCreate(['name' => 'Manajer Departemen']);
        $direkturRole       = Role::firstOrCreate(['name' => 'Direktur']);
        $karyawanRole       = Role::firstOrCreate(['name' => 'Karyawan']);
        $stafRole           = Role::firstOrCreate(['name' => 'Staf']);

        // Finance group (4 varian, semua dapat izin finance)
        $financeRole        = Role::firstOrCreate(['name' => 'Finance']);
        $financeManagerRole = Role::firstOrCreate(['name' => 'Finance Manager']);
        $financeStaffRole   = Role::firstOrCreate(['name' => 'Finance Staff']);
        $stafFinanceRole    = Role::firstOrCreate(['name' => 'Staf Finance']); // alias lama

        // HR group
        $hrManagerRole      = Role::firstOrCreate(['name' => 'HR Manager']);
        $hrStaffRole        = Role::firstOrCreate(['name' => 'HR Staff']);

        // IT
        $itSupportRole      = Role::firstOrCreate(['name' => 'IT Support']);

        $this->command->info('✅ Roles created/verified (12 roles).');

        // -------------------------------------------------------
        // PERMISSION ASSIGNMENTS
        // -------------------------------------------------------

        $essPermissions = ['pengajuan.create', 'pengajuan.view.own'];

        // ESS-only roles
        $stafRole->syncPermissions($essPermissions);
        $karyawanRole->syncPermissions($essPermissions);
        $itSupportRole->syncPermissions($essPermissions);

        // Manajer Departemen
        $manajerRole->syncPermissions([
            'pengajuan.create',
            'pengajuan.view.own',
            'pengajuan.view.department',
            'pengajuan.approve.level1',
            'pengajuan.reject',
            'budget.view.department',
        ]);

        // Direktur
        $direkturRole->syncPermissions([
            'pengajuan.create',
            'pengajuan.view.own',
            'pengajuan.view.all',
            'pengajuan.approve.level1',
            'pengajuan.approve.level2',
            'pengajuan.reject',
            'budget.view.all',
            'report.view.gl',
        ]);

        // Finance (semua varian mendapat izin identik)
        $financePermissions = [
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
        ];
        $financeRole->syncPermissions($financePermissions);
        $financeManagerRole->syncPermissions($financePermissions);
        $financeStaffRole->syncPermissions($financePermissions);
        $stafFinanceRole->syncPermissions($financePermissions);

        // HR
        $hrPermissions = [
            'karyawan.manage',
            'payroll.run',
            'payroll.view',
            'pengajuan.create',
            'pengajuan.view.own',
        ];
        $hrManagerRole->syncPermissions($hrPermissions);
        $hrStaffRole->syncPermissions($hrPermissions);

        // Super Admin — dapat semua
        $superAdminRole->givePermissionTo(Permission::all());

        $this->command->info('✅ Permissions assigned to all roles.');

        // -------------------------------------------------------
        // ASSIGN ROLES TO SEED USERS
        // -------------------------------------------------------
        $adminUser = User::where('email', 'admin@persija.id')->first();
        if ($adminUser && !$adminUser->hasRole('Super Admin')) {
            $adminUser->assignRole($superAdminRole);
            $this->command->info('✅ Super Admin → admin@persija.id');
        }

        $stafUser = User::where('email', 'staf.edp@persija.id')->first();
        if ($stafUser && !$stafUser->hasRole('Staf')) {
            $stafUser->assignRole($stafRole);
            $this->command->info('✅ Staf → staf.edp@persija.id');
        }

        $this->command->info('✅ RoleAndPermissionSeeder selesai.');
    }
}