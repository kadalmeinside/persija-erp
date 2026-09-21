<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\BudgetCheckService;
use App\Models\BudgetMaster;
use App\Models\BudgetDetail;
use App\Models\PeriodeAnggaran;
use App\Models\PosAnggaran;
use App\Models\AkunGl;
use App\Models\ProgramKerja;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $budgetId;

    protected function setUp(): void
    {
        parent::setUp();

        // FK off untuk SQLite testing agar dummy ID tidak error
        $this->disableForeignKeyConstraints();

        $this->service = new BudgetCheckService();

        // Setup Dasar
        $periode = PeriodeAnggaran::create([
            'nama_periode'   => 'Testing 2026',
            'tahun'          => 2026,
            'tanggal_mulai'  => '2026-01-01',
            'tanggal_selesai'=> '2026-12-31',
            'is_active'      => 1,
        ]);

        $akun = AkunGl::create([
            'kode_akun' => '5001',
            'nama_akun' => 'Biaya Test',
            'tipe_akun' => 'Biaya',
        ]);

        // Buat ProgramKerja agar FK tbl_pos_anggaran.id_program_kerja valid
        $program = ProgramKerja::create([
            'nama_program' => 'Program Test',
        ]);

        $pos = PosAnggaran::create([
            'id_program_kerja' => $program->id,
            'id_akun_gl'       => $akun->id,
        ]);

        $budget = BudgetMaster::create([
            'id_periode_anggaran'    => $periode->id,
            'id_pos_anggaran'        => $pos->id,
            'anggaran_total_tahun'   => 1000000, // 1 Juta
            'anggaran_terikat_ytd'   => 100000,  // Terpakai 100k
            'anggaran_realisasi_ytd' => 0,
        ]);

        $this->budgetId = $budget->id;

        // Pacing bulan April
        BudgetDetail::create([
            'id_budget_master' => $budget->id,
            'bulan'            => 4,
            'tahun'            => 2026,
            'nominal_pacing'   => 500000,
        ]);
    }

    public function test_commit_budget_increments_anggaran_terikat()
    {
        $this->service->commitBudget($this->budgetId, 50000);
        $budget = BudgetMaster::find($this->budgetId);

        $this->assertEquals(150000, $budget->anggaran_terikat_ytd);
    }

    public function test_uncommit_budget_decrements_anggaran_terikat()
    {
        $this->service->unCommitBudget($this->budgetId, 20000);
        $budget = BudgetMaster::find($this->budgetId);

        $this->assertEquals(80000, $budget->anggaran_terikat_ytd); // 100k - 20k
    }

    public function test_realize_budget_moves_terikat_to_realisasi()
    {
        $this->service->realizeBudget($this->budgetId, 50000);
        $budget = BudgetMaster::find($this->budgetId);

        $this->assertEquals(50000, $budget->anggaran_terikat_ytd); // 100k - 50k
        $this->assertEquals(50000, $budget->anggaran_realisasi_ytd);
    }

    public function test_get_sisa_saldo_returns_correct_amount()
    {
        // Total (1000k) - Terikat (100k) - Realisasi (0) = 900k
        $akun = AkunGl::first();
        $result = $this->service->getSisaSaldoDB(1, $akun->id, 1, Carbon::parse('2026-04-10'));

        $this->assertTrue($result['success']);
        $this->assertEquals(900000, $result['sisa_saldo_db']);
    }

    public function test_check_budget_success()
    {
        $akun = AkunGl::first();
        $result = $this->service->check(1, $akun->id, 1, 200000, Carbon::parse('2026-04-10'));

        $this->assertTrue($result['success']);
        $this->assertNull($result['warning']); // tidak melampaui pacing 500k
    }

    public function test_check_budget_fails_if_exceeds_yearly_limit()
    {
        $akun = AkunGl::first();
        $result = $this->service->check(1, $akun->id, 1, 1500000, Carbon::parse('2026-04-10')); // Sisa 900k

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Plafon tahunan terlampaui', $result['message']);
    }

    public function test_check_budget_returns_warning_if_exceeds_monthly_pacing()
    {
        $akun = AkunGl::first();
        $result = $this->service->check(1, $akun->id, 1, 600000, Carbon::parse('2026-04-10')); // Pacing 500k

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['warning']);
        $this->assertStringContainsString('Over Budget Bulanan', $result['warning']);
    }

    public function test_bypass_liability_accounts()
    {
        $utang = AkunGl::create([
            'kode_akun' => '2001',
            'nama_akun' => 'Hutang Retensi',
            'tipe_akun' => 'Utang', // Nilai valid: 'Aset', 'Utang', 'Modal', 'Pendapatan', 'Biaya', 'Biaya Modal'
        ]);

        $result = $this->service->check(1, $utang->id, 1, 100000000, Carbon::parse('2026-04-10'));

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Kewajiban', $result['message']);
    }
}
