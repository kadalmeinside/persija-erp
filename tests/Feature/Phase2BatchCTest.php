<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PengajuanStatus;
use App\Enums\PengajuanType;
use App\Enums\Role;
use App\Models\ApprovalDelegation;
use App\Models\ApprovalRule;
use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\PengajuanHeader;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\FinancialReconciliationService;
use App\Services\PayrollService;
use App\Services\PayrollStatutoryCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class Phase2BatchCTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (Role::cases() as $role) {
            SpatieRole::firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
        }
    }

    public function test_financial_reconciliation_and_active_delegation_work_properly(): void
    {
        $dept = Departemen::create(['kode_departemen' => 'FIN', 'nama_departemen' => 'Finance']);

        $pengajuUser = User::factory()->create();
        $pengaju = Karyawan::create([
            'user_id' => $pengajuUser->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'EMP-901',
            'nama_lengkap' => 'Pengaju',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
        ]);

        $approverUser = User::factory()->create();
        $approver = Karyawan::create([
            'user_id' => $approverUser->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'MGR-901',
            'nama_lengkap' => 'Manajer Cuti',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Manajer',
        ]);

        $delegateUser = User::factory()->create();
        $delegate = Karyawan::create([
            'user_id' => $delegateUser->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'MGR-902',
            'nama_lengkap' => 'Pelaksana Harian',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Manajer',
        ]);

        ApprovalDelegation::create([
            'delegator_user_id' => $approverUser->id,
            'delegate_user_id' => $delegateUser->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'is_active' => true,
            'reason' => 'Cuti Tahunan',
        ]);

        ApprovalRule::create([
            'tipe' => 'Pengajuan',
            'id_departemen' => $dept->id,
            'level_order' => 1,
            'id_karyawan_approver' => $approver->id,
            'label_aksi' => 'Manajer',
        ]);

        $pengajuan = PengajuanHeader::create([
            'nomor_pengajuan' => 'PJ-2026-9901',
            'id_pengaju' => $pengaju->id,
            'id_departemen' => $dept->id,
            'tgl_pengajuan' => '2026-11-20',
            'judul_pengajuan' => 'Test Delegasi',
            'tipe_pengajuan' => PengajuanType::LANGSUNG,
            'total_nominal_diajukan' => 1000000,
            'metode_pembayaran' => PaymentMethod::TRANSFER,
            'status_global' => PengajuanStatus::APPROVED,
        ]);

        app(ApprovalService::class)->initApproval($pengajuan);
        $this->assertDatabaseHas('tbl_approval_process', [
            'id_karyawan_target' => $delegate->id,
            'status' => 'Pending',
        ]);

        // Test FinancialReconciliationService no longer crashes on column names
        $reconcileService = app(FinancialReconciliationService::class);
        $pengajuanLog = $reconcileService->reconcilePengajuan('2026-11', $approverUser->id);
        $payrollLog = $reconcileService->reconcilePayroll('2026-11', $approverUser->id);

        $this->assertEquals('Pengajuan', $pengajuanLog->module);
        $this->assertEquals('Payroll', $payrollLog->module);
    }

    public function test_payroll_statutory_calculator_computes_bpjs_and_pph21_ter(): void
    {
        $calc = app(PayrollStatutoryCalculator::class);
        $res = $calc->calculate(10_000_000, 'TK/0');

        // BPJS Kes 1% = 100,000; JHT 2% + JP 1% = 300,000; TER Cat A <= 10.05jt = 2% = 200,000
        $this->assertEquals(100000.0, $res['bpjs_kesehatan']);
        $this->assertEquals(300000.0, $res['bpjs_ketenagakerjaan']);
        $this->assertEquals(200000.0, $res['pph21']);
        $this->assertEquals(600000.0, $res['total']);

        $dept = Departemen::create(['kode_departemen' => 'OPS', 'nama_departemen' => 'Operasional']);
        $creator = User::factory()->create();
        $karyawan = Karyawan::create([
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'EMP-905',
            'nama_lengkap' => 'Karyawan Pajak',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
            'gaji_pokok' => 10_000_000,
            'status_ptkp' => 'TK/0',
        ]);

        $this->actingAs($creator);
        $payroll = app(PayrollService::class)->generatePayroll('2026-12', '2026-12-25');
        $detail = $payroll->details()->where('id_karyawan', $karyawan->id)->first();

        $this->assertEquals(600000.0, (float) $detail->total_potongan);
        $this->assertEquals(9400000.0, (float) $detail->gaji_bersih);
    }
}
