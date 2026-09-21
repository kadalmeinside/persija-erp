<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * RoleAccessTest
 *
 * Security regression test: memverifikasi bahwa setiap role
 * dapat (atau tidak dapat) mengakses endpoint yang seharusnya.
 *
 * Catatan desain:
 *   - Akses dikontrol oleh route middleware (role:xxx) — BUKAN authorize() di controller
 *   - Jadi test ini murni mengetest role middleware, bukan Gate/Policy
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private Departemen $dept;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset permission cache agar seeder berlaku di test
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->dept = Departemen::create(['nama_departemen' => 'Test Dept']);

        // Inisialisasi permissions minimal yang dibutuhkan — sesuai RoleAndPermissionSeeder
        $this->seedPermissions();
    }

    // -------------------------------------------------------------------------
    // HELPER: seed permissions + roles (subset dari RoleAndPermissionSeeder)
    // -------------------------------------------------------------------------

    private function seedPermissions(): void
    {
        $perms = [
            'settings.manage', 'user.manage', 'role.manage',
            'pengajuan.create', 'pengajuan.view.own', 'pengajuan.view.all',
            'pengajuan.approve.level1', 'pengajuan.pay', 'pengajuan.reject',
            'budget.view.all', 'budget.manage',
            'karyawan.manage', 'payroll.run', 'payroll.view',
            'fakturjual.manage', 'po.manage',
        ];

        foreach ($perms as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Super Admin mendapat semua permission
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());
    }

    private function makeUserWithRole(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole($role);

        Karyawan::create([
            'user_id'              => $user->id,
            'nama_lengkap'         => "Test $roleName",
            'id_departemen'        => $this->dept->id,
            'jabatan'              => $roleName,
            'nomor_induk_karyawan' => 'ACL-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'gaji_pokok'           => 5_000_000,
            'status_karyawan'      => 'Tetap',
            'tgl_bergabung'        => now(),
        ]);

        return $user;
    }

    // =========================================================================
    // Super Admin — harus bisa akses semua halaman admin
    // =========================================================================

    /** @test */
    public function super_admin_can_access_dashboard(): void
    {
        $user = $this->makeUserWithRole('Super Admin', 'sa@test.com');
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    /** @test */
    public function super_admin_can_access_user_management(): void
    {
        $user = $this->makeUserWithRole('Super Admin', 'sa2@test.com');
        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
    }

    /** @test */
    public function super_admin_can_access_finance_settings(): void
    {
        $user = $this->makeUserWithRole('Super Admin', 'sa3@test.com');
        // Route dilindungi role:Super Admin — user ini harus bisa masuk
        $this->actingAs($user)->get(route('admin.finance-settings.index'))->assertOk();
    }

    // =========================================================================
    // Finance Staff — bisa akses halaman keuangan, tidak bisa Super Admin routes
    // =========================================================================

    /** @test */
    public function finance_staff_can_access_dashboard(): void
    {
        $user = $this->makeUserWithRole('Finance Staff', 'fs@test.com');
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    /** @test */
    public function finance_staff_can_access_budget(): void
    {
        $user = $this->makeUserWithRole('Finance Staff', 'fs2@test.com');
        $this->actingAs($user)->get(route('admin.budget.index'))->assertOk();
    }

    /** @test */
    public function finance_staff_cannot_access_user_management(): void
    {
        $user = $this->makeUserWithRole('Finance Staff', 'fs3@test.com');
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    /** @test */
    public function finance_staff_cannot_access_finance_settings(): void
    {
        // finance-settings hanya untuk Super Admin — bukan Finance Staff
        $user = $this->makeUserWithRole('Finance Staff', 'fs4@test.com');
        $this->actingAs($user)->get(route('admin.finance-settings.index'))->assertForbidden();
    }

    // =========================================================================
    // HR Staff — bisa kelola karyawan, tidak bisa finance
    // =========================================================================

    /** @test */
    public function hr_staff_can_access_karyawan(): void
    {
        $user = $this->makeUserWithRole('HR Staff', 'hr@test.com');
        $this->actingAs($user)->get(route('admin.karyawan.index'))->assertOk();
    }

    /** @test */
    public function hr_staff_cannot_access_budget(): void
    {
        $user = $this->makeUserWithRole('HR Staff', 'hr2@test.com');
        $this->actingAs($user)->get(route('admin.budget.index'))->assertForbidden();
    }

    // =========================================================================
    // Staf / Karyawan — hanya ESS (pengajuan sendiri)
    // =========================================================================

    /** @test */
    public function staf_can_access_my_pengajuan(): void
    {
        $user = $this->makeUserWithRole('Staf', 'staf@test.com');
        $this->actingAs($user)->get(route('admin.pengajuan.my-requests'))->assertOk();
    }

    /** @test */
    public function staf_cannot_access_all_pengajuan(): void
    {
        // index pengajuan untuk non-Finance harus redirect ke my-requests
        $user = $this->makeUserWithRole('Staf', 'staf2@test.com');
        $this->actingAs($user)
             ->get(route('admin.pengajuan.index'))
             ->assertRedirectToRoute('admin.pengajuan.my-requests');
    }

    /** @test */
    public function staf_cannot_access_karyawan_management(): void
    {
        $user = $this->makeUserWithRole('Staf', 'staf3@test.com');
        $this->actingAs($user)->get(route('admin.karyawan.index'))->assertForbidden();
    }

    // =========================================================================
    // IT Support — dashboard + ticket
    // =========================================================================

    /** @test */
    public function it_support_can_access_tickets(): void
    {
        $user = $this->makeUserWithRole('IT Support', 'it@test.com');
        $this->actingAs($user)->get(route('admin.tickets.index'))->assertOk();
    }

    /** @test */
    public function it_support_cannot_access_payroll(): void
    {
        $user = $this->makeUserWithRole('IT Support', 'it2@test.com');
        $this->actingAs($user)->get(route('admin.payrolls.index'))->assertForbidden();
    }

    // =========================================================================
    // Guest — semua halaman admin harus redirect ke login
    // =========================================================================

    /** @test */
    public function guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))
             ->assertRedirectToRoute('admin.login');
    }

    /** @test */
    public function guest_cannot_access_pengajuan(): void
    {
        $this->get(route('admin.pengajuan.index'))->assertRedirect();
    }
}
