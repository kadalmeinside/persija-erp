<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\Payroll;
use App\Models\PayrollDetail;

class EssModuleTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $karyawan;
    protected $dept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept = Departemen::create(['nama_departemen' => 'Operations', 'kode_departemen' => 'OPS']);

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->karyawan = Karyawan::create([
            'user_id' => $this->user->id,
            'id_departemen' => $this->dept->id,
            'nama_lengkap' => 'Staff ESS',
            'nomor_induk_karyawan' => 'ESS-001',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Specialist',
        ]);
    }

    public function test_karyawan_can_submit_and_view_koreksi_absensi()
    {
        $submit = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ess/attendance-correction', [
                'tanggal' => now()->subDay()->toDateString(),
                'waktu_masuk_baru' => '08:30',
                'waktu_keluar_baru' => '17:30',
                'alasan' => 'Lupa clock out karena rapat mendesak',
            ]);

        $submit->assertStatus(201)
            ->assertJsonPath('data.status', 'Pending');

        $list = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/ess/attendance-correction');

        $list->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_karyawan_can_submit_and_view_dinas_luar()
    {
        $submit = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ess/business-trip', [
                'nama_kegiatan' => 'Workshop ERP Enterprise di Bandung',
                'lokasi_tujuan' => 'Bandung',
                'tgl_mulai' => now()->addDays(2)->toDateString(),
                'tgl_selesai' => now()->addDays(4)->toDateString(),
                'keterangan' => 'Mewakili tim IT',
            ]);

        $submit->assertStatus(201)
            ->assertJsonPath('data.status', 'Pending');

        $this->assertNotEmpty($submit->json('data.nomor_surat_tugas'));

        $list = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/ess/business-trip');

        $list->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_karyawan_can_view_payslip_read_only()
    {
        $payroll = Payroll::create([
            'bulan_periode' => '2026-09',
            'tgl_payroll' => '2026-09-25',
            'total_gaji_kotor' => 10000000,
            'total_potongan' => 500000,
            'total_gaji_bersih' => 9500000,
            'status' => 'Approved',
        ]);

        $detail = PayrollDetail::create([
            'id_payroll' => $payroll->id,
            'id_karyawan' => $this->karyawan->id,
            'gaji_pokok' => 8000000,
            'total_tunjangan' => 2000000,
            'total_potongan' => 500000,
            'gaji_bersih' => 9500000,
            'rincian_komponen' => [
                'Tunjangan Transport' => 1000000,
                'Tunjangan Makan' => 1000000,
                'Potongan BPJS' => 500000,
            ],
        ]);

        $list = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/ess/payslips');

        $list->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.periode', '2026-09')
            ->assertJsonPath('data.0.gaji_bersih', 9500000);

        $detailResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/ess/payslips/{$detail->id}");

        $detailResponse->assertStatus(200)
            ->assertJsonPath('data.ringkasan.gaji_bersih', 9500000)
            ->assertJsonPath('data.rincian_komponen.Tunjangan Transport', 1000000);
    }

    public function test_karyawan_can_submit_and_view_reimbursement()
    {
        $submit = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/ess/reimbursements', [
                'judul_klaim' => 'Bensin & Tol Kunjungan Klien',
                'kategori' => 'Transport',
                'tgl_transaksi' => now()->subDays(2)->toDateString(),
                'total_biaya' => 250000,
                'keterangan' => 'Bukti struk terlampir',
            ]);

        $submit->assertStatus(201)
            ->assertJsonPath('data.status', 'Pending');

        $this->assertNotEmpty($submit->json('data.nomor_reimbursement'));

        $list = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/ess/reimbursements');

        $list->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
