<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PengajuanStatus;
use App\Enums\PengajuanType;
use App\Models\AkunGl;
use App\Models\ApprovalRule;
use App\Models\BudgetMaster;
use App\Models\Departemen;
use App\Models\GajiKomponen;
use App\Models\Karyawan;
use App\Models\KaryawanKomponenGaji;
use App\Models\PengajuanDetail;
use App\Models\PengajuanHeader;
use App\Models\PeriodeAnggaran;
use App\Models\PosAnggaran;
use App\Models\ProgramKerja;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\BudgetCheckService;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalAndPayrollIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejecting_pengajuan_uncommits_committed_budget(): void
    {
        $dept = Departemen::create(['kode_departemen' => 'OPS', 'nama_departemen' => 'Operasional']);
        $akun = AkunGl::create(['kode_akun' => '5101', 'nama_akun' => 'Beban Ops', 'tipe_akun' => 'Biaya']);
        $periode = PeriodeAnggaran::create([
            'nama_periode' => 'Test Period',
            'tanggal_mulai' => now()->startOfYear(),
            'tanggal_selesai' => now()->endOfYear(),
            'is_active' => true,
        ]);
        $program = ProgramKerja::create([
            'id_departemen' => $dept->id,
            'nama_program' => 'Program Ops',
        ]);
        $posAnggaran = PosAnggaran::create([
            'id_program_kerja' => $program->id,
            'id_akun_gl' => $akun->id,
            'is_active' => true,
        ]);

        $budget = BudgetMaster::create([
            'id_pos_anggaran' => $posAnggaran->id,
            'id_periode_anggaran' => $periode->id,
            'anggaran_total_tahun' => 10000000,
            'anggaran_terikat_ytd' => 0,
            'anggaran_realisasi_ytd' => 0,
        ]);

        $pengajuUser = User::factory()->create();
        $pengaju = Karyawan::create([
            'user_id' => $pengajuUser->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'EMP-01',
            'nama_lengkap' => 'Pengaju',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
        ]);

        $approverUser = User::factory()->create();
        $approver = Karyawan::create([
            'user_id' => $approverUser->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'MGR-01',
            'nama_lengkap' => 'Approver',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Manajer',
        ]);

        ApprovalRule::create([
            'tipe' => 'Pengajuan',
            'id_departemen' => $dept->id,
            'level_order' => 1,
            'id_karyawan_approver' => $approver->id,
            'label_aksi' => 'Manajer',
        ]);

        $pengajuan = PengajuanHeader::create([
            'nomor_pengajuan' => 'PJ-2026-1001',
            'id_pengaju' => $pengaju->id,
            'id_departemen' => $dept->id,
            'tgl_pengajuan' => now()->toDateString(),
            'judul_pengajuan' => 'Test Uncommit',
            'tipe_pengajuan' => PengajuanType::LANGSUNG,
            'total_nominal_diajukan' => 2500000,
            'metode_pembayaran' => PaymentMethod::TRANSFER,
            'status_global' => PengajuanStatus::PENDING_APPROVAL,
        ]);

        PengajuanDetail::create([
            'id_pengajuan' => $pengajuan->id,
            'id_program' => $program->id,
            'id_akun' => $akun->id,
            'deskripsi_item' => 'Item',
            'nominal_item' => 2500000,
        ]);

        app(BudgetCheckService::class)->commitBudget($budget->id, 2500000);
        $this->assertEquals(2500000.0, (float) $budget->fresh()->anggaran_terikat_ytd);

        $approvalService = app(ApprovalService::class);
        $approvalService->initApproval($pengajuan);
        $approvalService->reject($pengajuan, $approver->id, 'Ditolak');

        $this->assertEquals(PengajuanStatus::REJECTED, $pengajuan->fresh()->status_global);
        $this->assertEquals(0.0, (float) $budget->fresh()->anggaran_terikat_ytd);
    }

    public function test_payroll_prevents_duplicate_period_loads_routine_components_and_enforces_sod(): void
    {
        $dept = Departemen::create(['kode_departemen' => 'HR', 'nama_departemen' => 'HR']);
        $akun = AkunGl::create(['kode_akun' => '6101', 'nama_akun' => 'Beban Gaji', 'tipe_akun' => 'Biaya']);
        $creator = User::factory()->create();
        $karyawan = Karyawan::create([
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'EMP-77',
            'nama_lengkap' => 'Pemain',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
            'gaji_pokok' => 5000000,
        ]);

        $tunjangan = GajiKomponen::create([
            'nama_komponen' => 'Tunjangan Makan',
            'tipe' => 'pendapatan',
            'id_akun_gl' => $akun->id,
        ]);
        $bpjs = GajiKomponen::create([
            'nama_komponen' => 'Potongan BPJS',
            'tipe' => 'potongan',
            'id_akun_gl' => $akun->id,
        ]);

        KaryawanKomponenGaji::create([
            'id_karyawan' => $karyawan->id,
            'id_komponen_gaji' => $tunjangan->id,
            'nominal' => 500000,
            'is_active' => true,
        ]);
        KaryawanKomponenGaji::create([
            'id_karyawan' => $karyawan->id,
            'id_komponen_gaji' => $bpjs->id,
            'nominal' => 100000,
            'is_active' => true,
        ]);

        $this->actingAs($creator);
        $service = app(PayrollService::class);
        $payroll = $service->generatePayroll('2026-11', '2026-11-25');

        $detail = $payroll->details()->where('id_karyawan', $karyawan->id)->first();
        $this->assertNotNull($detail);
        $this->assertEquals(500000.0, (float) $detail->total_tunjangan);
        $this->assertEquals(333750.0, (float) $detail->total_potongan);
        $this->assertEquals(5166250.0, (float) $detail->gaji_bersih);

        // Duplicate period must fail
        try {
            $service->generatePayroll('2026-11', '2026-11-25');
            $this->fail('Expected DomainException on duplicate payroll period');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('2026-11', $e->getMessage());
        }

        // Same user cannot approve payroll they created (SoD)
        $this->expectException(\DomainException::class);
        $service->approvePayroll($payroll);
    }
}
