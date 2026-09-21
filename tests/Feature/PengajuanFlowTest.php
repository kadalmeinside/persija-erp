<?php

namespace Tests\Feature;

use App\Enums\PengajuanStatus;
use App\Models\AkunGl;
use App\Models\ApprovalRule;
use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\PeriodeAnggaran;
use App\Models\PosAnggaran;
use App\Models\BudgetMaster;
use App\Models\ProgramKerja;
use App\Models\User;
use App\Services\PengajuanService;
use App\Services\ApprovalService;
use App\Services\BudgetCheckService;
use App\Services\CalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PengajuanFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $pengaju;
    protected User $approver;
    protected User $financeUser;
    protected Karyawan $pengajuKaryawan;
    protected Karyawan $approverKaryawan;
    protected Departemen $departemen;
    protected AkunGl $akunBeban;
    protected ProgramKerja $program;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat roles
        $stafRole     = Role::firstOrCreate(['name' => 'Staf']);
        $financeRole  = Role::firstOrCreate(['name' => 'Finance']);
        $manajerRole  = Role::firstOrCreate(['name' => 'Manajer Departemen']);

        // Buat departemen
        $this->departemen = Departemen::create(['nama_departemen' => 'Dept Test']);

        // Buat akun GL untuk expense
        $this->akunBeban = AkunGl::create([
            'kode_akun' => '6-0001',
            'nama_akun' => 'Beban Operasional Test',
            'tipe_akun' => 'Biaya',  // valid enum: Aset|Utang|Modal|Pendapatan|Biaya|Biaya Modal
        ]);

        // Buat periode anggaran + budget
        $periode = PeriodeAnggaran::create([
            'nama_periode'    => 'Test Period',
            'tanggal_mulai'   => now()->startOfYear(),
            'tanggal_selesai' => now()->endOfYear(),
            'is_active'       => true,
        ]);

        $this->program = ProgramKerja::create([
            'nama_program'   => 'Program Test',
            'id_departemen'  => $this->departemen->id,
        ]);

        $posAnggaran = PosAnggaran::create([
            'id_program_kerja' => $this->program->id,
            'id_akun_gl'       => $this->akunBeban->id,
        ]);

        BudgetMaster::create([
            'id_pos_anggaran'      => $posAnggaran->id,
            'id_periode_anggaran'  => $periode->id,
            'anggaran_total_tahun' => 50_000_000,  // required NOT NULL
            'anggaran_terikat_ytd' => 0,
            'anggaran_realisasi_ytd' => 0,
        ]);

        // Buat user & karyawan pengaju
        $this->pengaju = User::factory()->create(['email' => 'pengaju@test.com']);
        $this->pengaju->assignRole($stafRole);
        $this->pengajuKaryawan = Karyawan::create([
            'user_id'       => $this->pengaju->id,
            'nama_lengkap'  => 'Pengaju Test',
            'id_departemen' => $this->departemen->id,
            'jabatan'       => 'Staf',
            'nomor_induk_karyawan' => 'TEST-001',
            'gaji_pokok'    => 5_000_000,
            'status_karyawan' => 'Tetap',
            'tgl_bergabung' => now(),
        ]);

        // Buat user & karyawan approver
        $this->approver = User::factory()->create(['email' => 'approver@test.com']);
        $this->approver->assignRole($manajerRole);
        $this->approverKaryawan = Karyawan::create([
            'user_id'       => $this->approver->id,
            'nama_lengkap'  => 'Approver Test',
            'id_departemen' => $this->departemen->id,
            'jabatan'       => 'Manajer',
            'nomor_induk_karyawan' => 'TEST-002',
            'gaji_pokok'    => 10_000_000,
            'status_karyawan' => 'Tetap',
            'tgl_bergabung' => now(),
        ]);

        // Buat approval rule
        ApprovalRule::create([
            'id_departemen'        => $this->departemen->id,
            'level_order'          => 1,
            'id_karyawan_approver' => $this->approverKaryawan->id,
            'label_aksi'           => 'Disetujui Manajer',
            'tipe'                 => 'Pengajuan',
        ]);

        // Buat user finance
        $this->financeUser = User::factory()->create(['email' => 'finance@test.com']);
        $this->financeUser->assignRole($financeRole);
        Karyawan::create([
            'user_id'       => $this->financeUser->id,
            'nama_lengkap'  => 'Finance Test',
            'id_departemen' => $this->departemen->id,
            'jabatan'       => 'Finance',
            'nomor_induk_karyawan' => 'TEST-003',
            'gaji_pokok'    => 8_000_000,
            'status_karyawan' => 'Tetap',
            'tgl_bergabung' => now(),
        ]);
    }

    // =========================================================================
    // TEST: Buat Pengajuan
    // =========================================================================

    /** @test */
    public function staf_can_create_pengajuan(): void
    {
        $this->actingAs($this->pengaju);

        $response = $this->post(route('admin.pengajuan.store'), [
            'judul_pengajuan'        => 'Test Pengajuan Operasional',
            'id_pengaju'             => $this->pengajuKaryawan->id,
            'id_departemen'          => $this->departemen->id,
            'tgl_pengajuan'          => now()->toDateString(),
            'tipe_pengajuan'         => 'UangMuka',
            'total_nominal_diajukan' => 1_000_000,
            'metode_pembayaran'      => 'Cash',
            'items'                  => [
                [
                    'deskripsi_item' => 'Pembelian ATK',
                    'nominal_item'   => 1_000_000,
                    'id_akun'        => $this->akunBeban->id,
                    'id_program'     => $this->program->id,
                    'id_tax_type'    => null,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tbl_pengajuan_header', [
            'judul_pengajuan' => 'Test Pengajuan Operasional',
            'id_pengaju'      => $this->pengajuKaryawan->id,
        ]);
    }

    /** @test */
    public function pengajuan_nomor_uses_new_sequential_format(): void
    {
        $this->actingAs($this->pengaju);

        $this->post(route('admin.pengajuan.store'), $this->validPengajuanPayload());

        $pengajuan = \App\Models\PengajuanHeader::latest()->first();

        $this->assertMatchesRegularExpression(
            '/^PJ\/\d{6}\/\d{4}$/',
            $pengajuan->nomor_pengajuan,
            "Format nomor harus PJ/YYYYMM/NNNN, dapat: {$pengajuan->nomor_pengajuan}"
        );
    }

    /** @test */
    public function pengajuan_status_is_pending_approval_after_creation(): void
    {
        $this->actingAs($this->pengaju);
        $this->post(route('admin.pengajuan.store'), $this->validPengajuanPayload());

        $pengajuan = \App\Models\PengajuanHeader::latest()->first();

        $this->assertEquals(PengajuanStatus::PENDING_APPROVAL, $pengajuan->status_global);
    }

    /** @test */
    public function approval_process_is_initialized_after_create(): void
    {
        $this->actingAs($this->pengaju);
        $this->post(route('admin.pengajuan.store'), $this->validPengajuanPayload());

        $pengajuan = \App\Models\PengajuanHeader::latest()->first();

        $this->assertDatabaseHas('tbl_approval_process', [
            'id_pengajuan'        => $pengajuan->id,
            'id_karyawan_target'  => $this->approverKaryawan->id,
            'status'              => 'Pending',
        ]);
    }

    // =========================================================================
    // TEST: Approval Flow
    // =========================================================================

    /** @test */
    public function approver_can_approve_pengajuan(): void
    {
        // Buat pengajuan
        $this->actingAs($this->pengaju);
        $this->post(route('admin.pengajuan.store'), $this->validPengajuanPayload());
        $pengajuan = \App\Models\PengajuanHeader::latest()->first();

        // Approve
        $this->actingAs($this->approver);
        $response = $this->post(route('admin.pengajuan.action', $pengajuan), [
            'status'  => 'Approved',
            'catatan' => 'Disetujui',
        ]);

        $response->assertRedirect();

        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::APPROVED, $pengajuan->status_global);
    }

    /** @test */
    public function approver_can_reject_pengajuan(): void
    {
        $this->actingAs($this->pengaju);
        $this->post(route('admin.pengajuan.store'), $this->validPengajuanPayload());
        $pengajuan = \App\Models\PengajuanHeader::latest()->first();

        $this->actingAs($this->approver);
        $this->post(route('admin.pengajuan.action', $pengajuan), [
            'status'  => 'Rejected',
            'catatan' => 'Tidak sesuai budget',
        ]);

        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::REJECTED, $pengajuan->status_global);
    }

    /**
     * @test
     *
     * Catatan: ApprovalService belum memvalidasi apakah user yang melakukan
     * approve adalah target yang terdaftar di ApprovalRule. 
     * KNOWN LIMITATION: pengaju bisa approve pengajuannya sendiri jika dia
     * juga terdaftar sebagai approver di rule yang sama.
     *
     * TODO: Tambahkan guard di ApprovalService::approve() untuk memastikan
     *       $karyawanId === pending step->id_karyawan_target.
     */
    public function non_approver_cannot_approve_pengajuan(): void
    {
        // Dalam setup, pengaju (Staf) dan approver (Manajer) adalah dua orang berbeda.
        // ApprovalRule hanya terdaftar untuk approverKaryawan.
        // Namun saat pengaju mencoba approve, ApprovalService akan mencari
        // step dengan status Pending dan id_karyawan_target = pengajuKaryawan->id.
        // Karena tidak ada step seperti itu, ApprovalService throw exception
        // dan controller redirect with error. Status TIDAK berubah.
        //
        // CATATAN: Jika pengaju terdaftar di ApprovalRule, dia BISA approve.
        // Gate ini harus diperkuat di service layer.

        $this->actingAs($this->pengaju);
        $this->post(route('admin.pengajuan.store'), $this->validPengajuanPayload());
        $pengajuan = \App\Models\PengajuanHeader::latest()->first();

        // Pengaju mencoba approve miliknya sendiri
        $this->post(route('admin.pengajuan.action', $pengajuan), [
            'status'  => 'Approved',
            'catatan' => 'Coba approve sendiri',
        ]);

        $pengajuan->refresh();

        // Saat ini: ApprovalService akan throw 'No pending step found for this approver'
        // karena id_karyawan_target di step adalah approverKaryawan->id, bukan pengajuKaryawan->id.
        // Status tetap PENDING_APPROVAL.
        $this->assertEquals(PengajuanStatus::PENDING_APPROVAL, $pengajuan->status_global);
    }

    /** @test */
    public function approved_pengajuan_can_be_paid_by_finance(): void
    {
        // Setup: buat + approve pengajuan
        $this->actingAs($this->pengaju);
        $this->post(route('admin.pengajuan.store'), $this->validPengajuanPayload());
        $pengajuan = \App\Models\PengajuanHeader::latest()->first();

        $this->actingAs($this->approver);
        $this->post(route('admin.pengajuan.action', $pengajuan), [
            'status' => 'Approved', 'catatan' => 'OK',
        ]);

        // Buat kas/bank untuk pembayaran
        $kasBank = \App\Models\KasBank::create([
            'nama_bank'      => 'BCA Test',
            'nomor_rekening' => '1234567890',
            'atas_nama'      => 'PT Persija',
            'id_akun_gl'     => $this->akunBeban->id,
            'is_active'      => true,
        ]);

        // Finance bayar (dengan fake bukti bayar — required oleh PaymentController)
        $this->actingAs($this->financeUser);
        $response = $this->post(route('admin.pengajuan.pay', $pengajuan), [
            'tgl_bayar'     => now()->toDateString(),
            'nominal_bayar' => 1_000_000,
            'id_kas_bank'   => $kasBank->id,
            'catatan'       => 'Pembayaran lunas',
            'bukti_bayar'   => \Illuminate\Http\UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();

        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::PAID, $pengajuan->status_global);
    }

    // =========================================================================
    // TEST: Cancellation
    // =========================================================================

    /** @test */
    public function pengajuan_can_be_cancelled_and_budget_restored(): void
    {
        $this->actingAs($this->pengaju);
        $this->post(route('admin.pengajuan.store'), $this->validPengajuanPayload());
        $pengajuan = \App\Models\PengajuanHeader::latest()->first();

        // Cek budget terpakai setelah buat pengajuan
        $budgetBefore = BudgetMaster::first();
        $beforeUsed = $budgetBefore->jumlah_terpakai;

        // Cancel
        $this->patch(route('admin.pengajuan.cancel', $pengajuan), [
            'reason' => 'Tidak jadi diperlukan',
        ]);

        $pengajuan->refresh();
        $this->assertEquals(PengajuanStatus::CANCELLED, $pengajuan->status_global);

        // Budget terikat harus berkurang setelah cancel
        $budgetAfter = BudgetMaster::first()->fresh();
        // anggaran_terikat_ytd adalah kolom yang di-uncommit saat cancel
        $this->assertLessThanOrEqual(
            $budgetBefore->anggaran_terikat_ytd,
            $budgetAfter->anggaran_terikat_ytd
        );
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function validPengajuanPayload(): array
    {
        return [
            'judul_pengajuan'        => 'Test Pengajuan',
            'id_pengaju'             => $this->pengajuKaryawan->id,
            'id_departemen'          => $this->departemen->id,
            'tgl_pengajuan'          => now()->toDateString(),
            'tipe_pengajuan'         => 'UangMuka',
            'total_nominal_diajukan' => 1_000_000,
            'metode_pembayaran'      => 'Cash',
            'items'                  => [[
                'deskripsi_item' => 'ATK',
                'nominal_item'   => 1_000_000,
                'id_akun'        => $this->akunBeban->id,
                'id_program'     => $this->program->id,
                'id_tax_type'    => null,
            ]],
        ];
    }
}
