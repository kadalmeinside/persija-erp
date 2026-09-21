<?php

namespace Tests\Feature;

use App\Enums\PengajuanStatus;
use App\Models\ApprovalProcess;
use App\Models\ApprovalRule;
use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\PengajuanHeader;
use App\Models\Pinjaman;
use App\Models\Setting;
use App\Models\AkunGl;
use App\Models\ProgramKerja;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalService $approvalService;
    protected Departemen $dept;
    protected Karyawan $pengajuKaryawan;
    protected Karyawan $approver1;
    protected Karyawan $approver2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approvalService = app(ApprovalService::class);

        $this->dept = Departemen::create(['nama_departemen' => 'Dept Approval Test']);

        // Buat karyawan
        $user1 = User::factory()->create();
        $this->pengajuKaryawan = Karyawan::create([
            'user_id'              => $user1->id,
            'nama_lengkap'         => 'Pengaju',
            'id_departemen'        => $this->dept->id,
            'jabatan'              => 'Staf',
            'nomor_induk_karyawan' => 'APR-001',
            'gaji_pokok'           => 5_000_000,
            'status_karyawan'      => 'Tetap',
            'tgl_bergabung'        => now(),
        ]);

        $user2 = User::factory()->create();
        $this->approver1 = Karyawan::create([
            'user_id'              => $user2->id,
            'nama_lengkap'         => 'Approver Level 1',
            'id_departemen'        => $this->dept->id,
            'jabatan'              => 'Manajer',
            'nomor_induk_karyawan' => 'APR-002',
            'gaji_pokok'           => 10_000_000,
            'status_karyawan'      => 'Tetap',
            'tgl_bergabung'        => now(),
        ]);

        $user3 = User::factory()->create();
        $this->approver2 = Karyawan::create([
            'user_id'              => $user3->id,
            'nama_lengkap'         => 'Approver Level 2',
            'id_departemen'        => $this->dept->id,
            'jabatan'              => 'Direktur',
            'nomor_induk_karyawan' => 'APR-003',
            'gaji_pokok'           => 20_000_000,
            'status_karyawan'      => 'Tetap',
            'tgl_bergabung'        => now(),
        ]);
    }

    // =========================================================================
    // TEST: initApproval
    // =========================================================================

    /** @test */
    public function init_approval_creates_pending_process_for_first_level(): void
    {
        $this->createRule(1, $this->approver1->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        $this->assertDatabaseHas('tbl_approval_process', [
            'id_pengajuan'        => $pengajuan->id,
            'id_karyawan_target'  => $this->approver1->id,
            'level_order'         => 1,
            'status'              => 'Pending',
        ]);
    }

    /** @test */
    public function init_approval_sets_subsequent_levels_to_waiting(): void
    {
        $this->createRule(1, $this->approver1->id);
        $this->createRule(2, $this->approver2->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        $this->assertDatabaseHas('tbl_approval_process', [
            'id_pengajuan'        => $pengajuan->id,
            'id_karyawan_target'  => $this->approver2->id,
            'level_order'         => 2,
            'status'              => 'Waiting',
        ]);
    }

    /** @test */
    public function init_approval_sets_pengajuan_status_to_pending_approval(): void
    {
        $this->createRule(1, $this->approver1->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::PENDING_APPROVAL, $pengajuan->status_global);
    }

    /** @test */
    public function init_approval_does_nothing_when_no_rules_exist(): void
    {
        // Tidak ada ApprovalRule untuk departemen ini
        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        $this->assertDatabaseCount('tbl_approval_process', 0);
    }

    // =========================================================================
    // TEST: approve — single level
    // =========================================================================

    /** @test */
    public function approve_single_level_sets_pengajuan_to_approved(): void
    {
        $this->createRule(1, $this->approver1->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        $this->approvalService->approve($pengajuan, $this->approver1->id, 'Setuju');

        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::APPROVED, $pengajuan->status_global);
    }

    /** @test */
    public function approve_first_level_advances_to_second_level(): void
    {
        $this->createRule(1, $this->approver1->id);
        $this->createRule(2, $this->approver2->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        // Level 1 approve
        $this->approvalService->approve($pengajuan, $this->approver1->id, 'Level 1 OK');

        // Level 2 harus jadi Pending, pengajuan masih Pending Approval
        $this->assertDatabaseHas('tbl_approval_process', [
            'id_pengajuan'        => $pengajuan->id,
            'id_karyawan_target'  => $this->approver2->id,
            'status'              => 'Pending',
        ]);

        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::PENDING_APPROVAL, $pengajuan->status_global);
    }

    /** @test */
    public function approve_all_levels_sets_pengajuan_to_approved(): void
    {
        $this->createRule(1, $this->approver1->id);
        $this->createRule(2, $this->approver2->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        $this->approvalService->approve($pengajuan, $this->approver1->id, 'L1 OK');
        $this->approvalService->approve($pengajuan, $this->approver2->id, 'L2 OK');

        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::APPROVED, $pengajuan->status_global);
    }

    /** @test */
    public function approve_step_records_uuid(): void
    {
        $this->createRule(1, $this->approver1->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);
        $this->approvalService->approve($pengajuan, $this->approver1->id);

        $step = ApprovalProcess::where('id_pengajuan', $pengajuan->id)->first();
        $this->assertNotNull($step->uuid, 'Step yang di-approve harus memiliki UUID');
    }

    // =========================================================================
    // TEST: reject
    // =========================================================================

    /** @test */
    public function reject_sets_pengajuan_to_rejected(): void
    {
        $this->createRule(1, $this->approver1->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        $this->approvalService->reject($pengajuan, $this->approver1->id, 'Tidak valid');

        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::REJECTED, $pengajuan->status_global);
    }

    /** @test */
    public function reject_updates_the_step_to_rejected(): void
    {
        $this->createRule(1, $this->approver1->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);
        $this->approvalService->reject($pengajuan, $this->approver1->id, 'Alasan penolakan');

        $this->assertDatabaseHas('tbl_approval_process', [
            'id_pengajuan'       => $pengajuan->id,
            'id_karyawan_target' => $this->approver1->id,
            'status'             => 'Rejected',
        ]);
    }

    /** @test */
    public function approve_throws_if_no_pending_step_exists(): void
    {
        $pengajuan = $this->makePengajuan();
        // Tidak init approval — tidak ada step pending

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/tidak ada persetujuan/i');

        $this->approvalService->approve($pengajuan, $this->approver1->id);
    }

    // =========================================================================
    // TEST: Segregation of Duties — Auto-Skip & Identity Guard
    // =========================================================================

    /** @test */
    public function self_approver_step_is_skipped_during_init(): void
    {
        // Satu-satunya approver adalah pengaju sendiri → Conflict of Interest
        $this->createRule(1, $this->pengajuKaryawan->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        // Step tercatat sebagai 'Skipped'
        $this->assertDatabaseHas('tbl_approval_process', [
            'id_pengajuan'       => $pengajuan->id,
            'id_karyawan_target' => $this->pengajuKaryawan->id,
            'status'             => 'Skipped',
        ]);

        // Karena semua step di-skip → auto-approved
        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::APPROVED, $pengajuan->status_global);
    }

    /** @test */
    public function self_approver_is_skipped_but_next_valid_approver_is_activated(): void
    {
        // Level 1: pengaju sendiri → di-skip
        // Level 2: approver valid → diaktifkan sebagai Pending
        $this->createRule(1, $this->pengajuKaryawan->id);
        $this->createRule(2, $this->approver1->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        $this->assertDatabaseHas('tbl_approval_process', [
            'id_pengajuan'       => $pengajuan->id,
            'id_karyawan_target' => $this->pengajuKaryawan->id,
            'status'             => 'Skipped',
        ]);

        // Level 2 langsung Pending (bukan Waiting)
        $this->assertDatabaseHas('tbl_approval_process', [
            'id_pengajuan'       => $pengajuan->id,
            'id_karyawan_target' => $this->approver1->id,
            'status'             => 'Pending',
        ]);

        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::PENDING_APPROVAL, $pengajuan->status_global);
    }

    /** @test */
    public function identity_guard_blocks_wrong_approver(): void
    {
        $this->createRule(1, $this->approver1->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        // pengajuKaryawan mencoba approve padahal bukan target step ini
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/tidak berwenang/i');

        $this->approvalService->approve($pengajuan, $this->pengajuKaryawan->id, 'coba approve sendiri');
    }

    /** @test */
    public function identity_guard_allows_correct_approver(): void
    {
        $this->createRule(1, $this->approver1->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        // approver1 yang benar → tidak exception
        $this->approvalService->approve($pengajuan, $this->approver1->id, 'Setuju');

        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::APPROVED, $pengajuan->status_global);
    }

    // =========================================================================
    // TEST: revision
    // =========================================================================

    /** @test */
    public function revision_sets_pengajuan_status_to_revision(): void
    {
        $this->createRule(1, $this->approver1->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        $this->approvalService->revision($pengajuan, $this->approver1->id, 'Lampiran kurang lengkap');

        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::REVISION, $pengajuan->status_global);
    }

    /** @test */
    public function revision_updates_step_status_to_revision(): void
    {
        $this->createRule(1, $this->approver1->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);
        $this->approvalService->revision($pengajuan, $this->approver1->id, 'Data tidak lengkap');

        $this->assertDatabaseHas('tbl_approval_process', [
            'id_pengajuan'       => $pengajuan->id,
            'id_karyawan_target' => $this->approver1->id,
            'status'             => 'Revision',
            'catatan'            => 'Data tidak lengkap',
        ]);
    }

    /** @test */
    public function revision_identity_guard_blocks_wrong_approver(): void
    {
        $this->createRule(1, $this->approver1->id);

        $pengajuan = $this->makePengajuan();
        $this->approvalService->initApproval($pengajuan);

        // pengajuKaryawan mencoba minta revisi padahal bukan target step
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/tidak berwenang/i');

        $this->approvalService->revision($pengajuan, $this->pengajuKaryawan->id, 'Coba-coba');
    }

    /** @test */
    public function revision_throws_if_no_pending_step(): void
    {
        $pengajuan = $this->makePengajuan();
        // Tidak init approval — tidak ada step pending

        $this->expectException(\Exception::class);
        $this->approvalService->revision($pengajuan, $this->approver1->id, 'Tidak ada step');
    }

    private function createRule(int $level, int $karyawanId): ApprovalRule
    {
        return ApprovalRule::create([
            'id_departemen'        => $this->dept->id,
            'level_order'          => $level,
            'id_karyawan_approver' => $karyawanId,
            'label_aksi'           => "Approval Level $level",
            'tipe'                 => 'Pengajuan',
        ]);
    }

    private function makePengajuan(): PengajuanHeader
    {
        return PengajuanHeader::create([
            'nomor_pengajuan'        => 'PJ/TEST/' . rand(1000, 9999),
            'judul_pengajuan'        => 'Test Pengajuan',
            'id_pengaju'             => $this->pengajuKaryawan->id,
            'id_departemen'          => $this->dept->id,
            'tgl_pengajuan'          => now(),
            'tipe_pengajuan'         => 'UangMuka',  // valid: Langsung|UangMuka|Reimburse
            'total_nominal_diajukan' => 1_000_000,
            'status_global'          => PengajuanStatus::PENDING_APPROVAL,
            'metode_pembayaran'      => 'Cash',
        ]);
    }

    // =========================================================================
    // TEST: PINJAMAN
    // =========================================================================

    private function createPinjamanRule(int $level, int $karyawanId): ApprovalRule
    {
        return ApprovalRule::create([
            'id_departemen'        => $this->dept->id,
            'level_order'          => $level,
            'id_karyawan_approver' => $karyawanId,
            'label_aksi'           => "Approval Level $level",
            'tipe'                 => 'Pinjaman',
        ]);
    }

    private function makePinjaman(): Pinjaman
    {
        return Pinjaman::create([
            'id_karyawan'     => $this->pengajuKaryawan->id,
            'jumlah_pinjaman' => 5_000_000,
            'tenor_bulan'     => 10,
            'tanggal_pengajuan' => now(),
            'status'          => PengajuanStatus::DRAFT->value,
            'keterangan'      => 'Test Pinjaman',
            'jumlah_angsuran_per_bulan' => 500_000
        ]);
    }

    /** @test */
    public function pinjaman_init_approval_creates_pending_process(): void
    {
        $this->createPinjamanRule(1, $this->approver1->id);

        $pinjaman = $this->makePinjaman();
        $this->approvalService->initApproval($pinjaman);

        $this->assertDatabaseHas('tbl_approval_process', [
            'id_pinjaman'         => $pinjaman->id,
            'id_karyawan_target'  => $this->approver1->id,
            'level_order'         => 1,
            'status'              => 'Pending',
        ]);

        $pinjaman->refresh();
        $this->assertEquals(PengajuanStatus::PENDING_APPROVAL->value, $pinjaman->status);
    }

    /** @test */
    public function pinjaman_approve_sets_status_to_approved_and_triggers_payment_request(): void
    {
        $this->createPinjamanRule(1, $this->approver1->id);

        // Mock Setting and AkunGl
        $akunGl = AkunGl::create(['kode_akun' => '11-10', 'nama_akun' => 'Piutang Karyawan', 'tipe_akun' => 'Aset', 'saldo_normal' => 'Debit']);
        Setting::create(['key' => 'account_receivable_employee', 'value' => (string) $akunGl->id]);
        
        $programKerja = ProgramKerja::create(['nama_program' => 'Kasbon Karyawan', 'id_departemen' => $this->dept->id, 'tahun' => '2026', 'status' => 'Aktif']);
        Setting::create(['key' => 'default_program_loans', 'value' => (string) $programKerja->id]);

        $pinjaman = $this->makePinjaman();
        $this->approvalService->initApproval($pinjaman);
        
        $this->approvalService->approve($pinjaman, $this->approver1->id, 'Setuju Pinjaman');

        $pinjaman->refresh();
        $this->assertEquals(PengajuanStatus::APPROVED->value, $pinjaman->status);

        // Memastikan Pengajuan Dana dibuat secara otomatis
        $this->assertDatabaseHas('tbl_pengajuan_header', [
            'tipe_pengajuan' => 'Langsung',
            'judul_pengajuan' => 'Pencairan Pinjaman: ' . $pinjaman->karyawan->nama_lengkap,
            'catatan_header' => 'Auto-generated from Pinjaman ID: ' . $pinjaman->id . '. ' . $pinjaman->keterangan,
            'status_global' => PengajuanStatus::PENDING_APPROVAL->value
        ]);
    }

    /** @test */
    public function pinjaman_reject_sets_status_to_rejected(): void
    {
        $this->createPinjamanRule(1, $this->approver1->id);

        $pinjaman = $this->makePinjaman();
        $this->approvalService->initApproval($pinjaman);
        $this->approvalService->reject($pinjaman, $this->approver1->id, 'Tolak Pinjaman');

        $pinjaman->refresh();
        $this->assertEquals(PengajuanStatus::REJECTED->value, $pinjaman->status);
    }
}
