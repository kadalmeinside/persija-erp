<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\PengajuanHeader;
use App\Models\User;
use App\Enums\PaymentMethod;
use App\Enums\PengajuanStatus;
use App\Enums\PengajuanType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class PinAndCredentialSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (Role::cases() as $role) {
            SpatieRole::firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
        }
    }

    public function test_web_pengajuan_approval_requires_backend_pin_or_valid_pin_session(): void
    {
        $dept = Departemen::create(['kode_departemen' => 'FIN', 'nama_departemen' => 'Finance']);

        $pengaju = User::factory()->create();
        $pengajuKaryawan = Karyawan::create([
            'user_id' => $pengaju->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'EMP-10',
            'nama_lengkap' => 'Pengaju',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
        ]);

        $approver = User::factory()->create(['pin' => Hash::make('654321')]);
        $approver->assignRole(Role::MANAJER_DEPARTEMEN->value);
        Karyawan::create([
            'user_id' => $approver->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'MGR-10',
            'nama_lengkap' => 'Manajer',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Manajer',
        ]);

        $pengajuan = PengajuanHeader::create([
            'nomor_pengajuan' => 'PJ-2026-9991',
            'id_pengaju' => $pengajuKaryawan->id,
            'id_departemen' => $dept->id,
            'tgl_pengajuan' => now()->toDateString(),
            'judul_pengajuan' => 'Uji PIN',
            'tipe_pengajuan' => PengajuanType::LANGSUNG,
            'total_nominal_diajukan' => 100000,
            'metode_pembayaran' => PaymentMethod::TRANSFER,
            'status_global' => PengajuanStatus::PENDING_APPROVAL,
        ]);

        $response = $this->actingAs($approver)->post(route('admin.pengajuan.action', $pengajuan->id), [
            'status' => 'Approved',
        ]);

        $response->assertSessionHasErrors('pin');
    }

    public function test_pin_verification_locks_out_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['pin' => Hash::make('246810')]);

        for ($i = 0; $i < 5; $i++) {
            $res = $this->actingAs($user)->postJson(route('admin.auth.verify-pin'), [
                'pin' => '000000',
            ]);
            $this->assertContains($res->status(), [403, 429]);
        }

        // 6th attempt even with correct PIN should be locked out (429)
        $lockedRes = $this->actingAs($user)->postJson(route('admin.auth.verify-pin'), [
            'pin' => '246810',
        ]);
        $lockedRes->assertStatus(429);
    }

    public function test_generate_user_uses_random_password_null_pin_and_enforces_rotation(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole(Role::HR_MANAGER->value);

        $dept = Departemen::create(['kode_departemen' => 'OPS', 'nama_departemen' => 'Operasional']);
        $karyawan = Karyawan::create([
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'EMP-900',
            'nama_lengkap' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
        ]);

        $res = $this->actingAs($hr)->post(route('admin.karyawan.generate-user', $karyawan->id));
        $res->assertRedirect();

        $karyawan->refresh();
        $createdUser = $karyawan->user;
        $this->assertNotNull($createdUser);
        $this->assertFalse(Hash::check('persija123', $createdUser->password));
        $this->assertNull($createdUser->pin);
        $this->assertTrue($createdUser->must_change_password);
        $this->assertTrue($createdUser->must_change_pin);

        // First login must be redirected to profile.edit until password is rotated
        $this->actingAs($createdUser)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('profile.edit'));

        // After updating password, must_change_password is cleared
        $this->actingAs($createdUser)
            ->put(route('password.update'), [
                'current_password' => 'wrong-or-check-direct',
                'password' => 'NewSecurePass123!',
                'password_confirmation' => 'NewSecurePass123!',
            ]);
        // Force password rotation directly to test clearing behavior via PasswordController
        $createdUser->update(['password' => Hash::make('TempPass123!')]);
        $this->actingAs($createdUser)
            ->put(route('password.update'), [
                'current_password' => 'TempPass123!',
                'password' => 'NewSecurePass123!',
                'password_confirmation' => 'NewSecurePass123!',
            ])
            ->assertRedirect();

        $this->assertFalse($createdUser->fresh()->must_change_password);
    }
}
