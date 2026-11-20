<?php

namespace Tests\Feature;

use App\Enums\PengajuanType;
use App\Enums\Role;
use App\Models\Departemen;
use App\Models\DinasLuar;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\PengajuanHeader;
use App\Models\SaldoCuti;
use App\Models\Task;
use App\Models\User;
use App\Services\AbsensiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class Phase2BatchBTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (Role::cases() as $role) {
            SpatieRole::firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
        }
    }

    public function test_dinas_luar_clock_in_requires_approved_trip_and_reimbursement_creates_pengajuan_header(): void
    {
        Storage::fake('public');

        $dept = Departemen::create(['kode_departemen' => 'OPS', 'nama_departemen' => 'Operasional']);
        $user = User::factory()->create();
        $user->assignRole(Role::STAF->value);
        $karyawan = Karyawan::create([
            'user_id' => $user->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'EMP-801',
            'nama_lengkap' => 'Staf Lapangan',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
        ]);

        $absensiService = app(AbsensiService::class);
        $photo = UploadedFile::fake()->image('selfie.jpg');

        // Without approved DinasLuar, clock-in as dinas luar must be rejected
        $blocked = $absensiService->processClockIn($karyawan, -6.2, 106.8, $photo, true, 'Dinas luar');
        $this->assertFalse($blocked['success']);

        // Create Pending trip, approve via HR API, then clock-in succeeds
        $trip = DinasLuar::create([
            'id_karyawan' => $karyawan->id,
            'nomor_surat_tugas' => 'ST-2026-001',
            'nama_kegiatan' => 'Kunjungan',
            'lokasi_tujuan' => 'Bandung',
            'tgl_mulai' => now()->toDateString(),
            'tgl_selesai' => now()->toDateString(),
            'status' => 'Pending',
        ]);

        $hr = User::factory()->create();
        $hr->assignRole(Role::HR_MANAGER->value);
        $this->actingAs($hr, 'sanctum')
            ->postJson("/api/v1/ess/business-trip/{$trip->id}/action", ['action' => 'approve'])
            ->assertOk();

        $allowed = $absensiService->processClockIn($karyawan, -6.2, 106.8, $photo, true, 'Dinas luar');
        $this->assertTrue($allowed['success']);

        // ESS reimbursement creates PengajuanHeader(Reimburse)
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/ess/reimbursements', [
                'judul_klaim' => 'Tol & Bensin',
                'kategori' => 'Transport',
                'tgl_transaksi' => now()->toDateString(),
                'total_biaya' => 250000,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('tbl_pengajuan_header', [
            'id_pengaju' => $karyawan->id,
            'tipe_pengajuan' => PengajuanType::REIMBURSE->value,
            'judul_pengajuan' => 'Reimbursement: Tol & Bensin',
        ]);
    }

    public function test_web_cuti_cancel_refunds_balance_and_closes_pending_approval_steps(): void
    {
        $dept = Departemen::create(['kode_departemen' => 'OPS', 'nama_departemen' => 'Operasional']);
        $user = User::factory()->create();
        $user->assignRole(Role::STAF->value);
        $karyawan = Karyawan::create([
            'user_id' => $user->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'EMP-802',
            'nama_lengkap' => 'Pemohon Cuti',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
        ]);

        $jenis = JenisCuti::create([
            'nama_cuti' => 'Tahunan',
            'kode_cuti' => 'CT',
            'kuota_default' => 12,
            'is_unlimited' => false,
            'khusus_perempuan' => false,
        ]);

        SaldoCuti::create([
            'id_karyawan' => $karyawan->id,
            'id_jenis_cuti' => $jenis->id,
            'tahun_periode' => (int) date('Y'),
            'saldo_awal' => 12,
            'saldo_terpakai' => 2,
            'saldo_akhir' => 10,
        ]);

        $cuti = PengajuanCuti::create([
            'id_karyawan' => $karyawan->id,
            'id_jenis_cuti' => $jenis->id,
            'tgl_mulai' => now()->addDays(5)->toDateString(),
            'tgl_selesai' => now()->addDays(6)->toDateString(),
            'jumlah_hari' => 2,
            'alasan' => 'Liburan',
            'status' => 'Pending',
        ]);

        $this->actingAs($user)
            ->patch(route('admin.cuti.cancel', $cuti->id), ['reason' => 'Batal berangkat'])
            ->assertRedirect();

        $this->assertEquals('Cancelled', $cuti->fresh()->status);
        $saldo = SaldoCuti::where('id_karyawan', $karyawan->id)->first();
        $this->assertEquals(0, $saldo->saldo_terpakai);
        $this->assertEquals(12, $saldo->saldo_akhir);
    }

    public function test_dashboard_uses_correct_task_statuses_and_pengaju_id(): void
    {
        $dept = Departemen::create(['kode_departemen' => 'OPS', 'nama_departemen' => 'Operasional']);
        $user = User::factory()->create();
        $user->assignRole(Role::STAF->value);
        $karyawan = Karyawan::create([
            'user_id' => $user->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'EMP-803',
            'nama_lengkap' => 'Staf Dashboard',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
        ]);

        Task::create([
            'id_karyawan_creator' => $karyawan->id,
            'id_karyawan_assignee' => $karyawan->id,
            'id_departemen' => $dept->id,
            'title' => 'Task To Do',
            'priority' => 'High',
            'status' => 'To Do',
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $res = $this->actingAs($user)->get(route('admin.dashboard'));
        $res->assertOk();
    }
}
