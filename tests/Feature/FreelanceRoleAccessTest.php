<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\LokasiKantor;
use App\Enums\Role as RoleEnum;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FreelanceRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $freelanceUser;
    private Karyawan $freelanceKaryawan;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => RoleEnum::FREELANCE->value]);
        Role::firstOrCreate(['name' => RoleEnum::SUPER_ADMIN->value]);
        Role::firstOrCreate(['name' => RoleEnum::HR_STAFF->value]);

        $dept = Departemen::firstOrCreate(['nama_departemen' => 'Media & Content']);
        $lokasi = LokasiKantor::firstOrCreate(
            ['nama_kantor' => 'Kantor Pusat'],
            ['latitude' => -6.2, 'longitude' => 106.8, 'radius_meter' => 100, 'is_active' => true]
        );

        $this->freelanceUser = User::factory()->create([
            'email' => 'freelance.test@persija.id',
            'password' => bcrypt('password'),
        ]);
        $this->freelanceUser->assignRole(RoleEnum::FREELANCE->value);

        $this->freelanceKaryawan = Karyawan::create([
            'user_id' => $this->freelanceUser->id,
            'nama_lengkap' => 'Freelancer Fotografer',
            'nomor_induk_karyawan' => 'FL-001',
            'id_departemen' => $dept->id,
            'id_lokasi_kantor' => $lokasi->id,
            'jabatan' => 'Photographer Freelance',
            'status_karyawan' => 'Tetap',
            'tgl_bergabung' => '2026-01-01',
        ]);
    }

    public function test_freelance_can_access_web_attendance_clock_and_history(): void
    {
        $responseClock = $this->actingAs($this->freelanceUser)->get(route('admin.absensi.clock'));
        $responseClock->assertOk();

        $responseHistory = $this->actingAs($this->freelanceUser)->get(route('admin.absensi.my-attendance'));
        $responseHistory->assertOk();
    }

    public function test_freelance_accessing_web_dashboard_redirects_to_attendance_clock(): void
    {
        $response = $this->actingAs($this->freelanceUser)->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.absensi.clock'));
    }

    public function test_freelance_is_blocked_from_other_web_modules(): void
    {
        // Blocked from Cuti
        $responseCuti = $this->actingAs($this->freelanceUser)->get(route('admin.cuti.my-requests'));
        $responseCuti->assertForbidden();

        // Blocked from Pengajuan Dana
        $responsePengajuan = $this->actingAs($this->freelanceUser)->get(route('admin.pengajuan.my-requests'));
        $responsePengajuan->assertForbidden();

        // Blocked from Rekap Absensi HR
        $responseRekap = $this->actingAs($this->freelanceUser)->get(route('admin.absensi.rekap'));
        $responseRekap->assertForbidden();
    }

    public function test_freelance_can_access_api_attendance_endpoints(): void
    {
        $token = $this->freelanceUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/absensi/today');

        $response->assertOk();
    }

    public function test_freelance_is_blocked_from_non_attendance_api_endpoints(): void
    {
        $token = $this->freelanceUser->createToken('test-token')->plainTextToken;

        // Blocked from Cuti API
        $responseCuti = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/cuti/requests');
        $responseCuti->assertStatus(403);

        // Blocked from Tasks API
        $responseTasks = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/tasks');
        $responseTasks->assertStatus(403);

        // Blocked from ESS API
        $responseEss = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/ess/payslips');
        $responseEss->assertStatus(403);

        // Blocked from Approvals API
        $responseApprovals = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/approvals');
        $responseApprovals->assertStatus(403);
    }
}
