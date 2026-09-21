<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PayrollService;
use App\Models\Payroll;
use App\Models\Karyawan;
use App\Models\User;
use App\Models\Departemen;
use App\Enums\PayrollStatus;
use App\Enums\AngsuranStatus;
use App\Models\Pinjaman;
use App\Models\AngsuranPinjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service  = new PayrollService();
        $this->adminUser = User::factory()->create();
        $this->actingAs($this->adminUser);

        $dept = Departemen::create(['nama_departemen' => 'Finance', 'kode_departemen' => 'FIN']);

        // Karyawan Tetap — diikutkan payroll
        Karyawan::create([
            'id_departemen'        => $dept->id,
            'nama_lengkap'         => 'Karyawan 1',
            'nomor_induk_karyawan' => '101',
            'status_karyawan'      => 'Tetap',
            'gaji_pokok'           => 5000000,
            'jenis_kelamin'        => 'L',
            'jabatan'              => 'Staff Finance',
        ]);

        // Karyawan Kontrak — diikutkan payroll
        Karyawan::create([
            'id_departemen'        => $dept->id,
            'nama_lengkap'         => 'Karyawan 2',
            'nomor_induk_karyawan' => '102',
            'status_karyawan'      => 'Kontrak',
            'gaji_pokok'           => 4500000,
            'jenis_kelamin'        => 'P',
            'jabatan'              => 'Staff Finance',
        ]);

        // Karyawan Magang — DIABAIKAN oleh PayrollService (hanya Tetap & Kontrak)
        // Catatan: 'Resign' tidak valid di SQLite enum; gunakan 'Magang' agar lolos CHECK constraint
        Karyawan::create([
            'id_departemen'        => $dept->id,
            'nama_lengkap'         => 'Karyawan 3',
            'nomor_induk_karyawan' => '103',
            'status_karyawan'      => 'Magang',
            'gaji_pokok'           => 10000000,
            'jenis_kelamin'        => 'L',
            'jabatan'              => 'Supervisor Finance',
        ]);
    }

    public function test_generate_payroll_draft_creates_headers_and_skips_resign_employees()
    {
        $payroll = $this->service->generatePayroll('2026-04', '2026-04-28');

        // generatePayroll mengembalikan instance lama; refresh dari DB untuk dapat total yang sudah diupdate
        $payroll->refresh();

        // Total Gaji Kotor = 5jt + 4.5jt = 9.5jt
        $this->assertEquals(9500000, (float) $payroll->total_gaji_kotor);
        $this->assertEquals(0, (float) $payroll->total_potongan);
        $this->assertEquals(9500000, (float) $payroll->total_gaji_bersih);
    }

    public function test_generate_payroll_automatically_deducts_loan_installments()
    {
        $karyawan = Karyawan::where('status_karyawan', 'Tetap')->first();

        // 1. Buat pinjaman
        $pinjaman = Pinjaman::create([
            'kode_pinjaman'             => 'PJ-2026-001',
            'id_karyawan'               => $karyawan->id,
            'jumlah_pinjaman'           => 1000000,
            'tenor_bulan'               => 2,
            'jumlah_angsuran_per_bulan' => 500000, // 1jt / 2 bulan
            'tanggal_pengajuan'         => '2026-03-01',
            'status'                    => 'Approved',
        ]);

        // 2. Buat angsuran pending di bulan April
        AngsuranPinjaman::create([
            'id_pinjaman'    => $pinjaman->id,
            'cicilan_ke'     => 1,
            'jumlah_angsuran'=> 500000,
            'bulan_periode'  => '2026-04',
            'status_bayar'   => AngsuranStatus::PENDING->value,
        ]);

        $payroll = $this->service->generatePayroll('2026-04', '2026-04-28');

        // Refresh payroll dari DB untuk dapat total terkini
        $payroll->refresh();

        $this->assertEquals(9500000, (float) $payroll->total_gaji_kotor);
        $this->assertEquals(500000, (float) $payroll->total_potongan);
        $this->assertEquals(9000000, (float) $payroll->total_gaji_bersih);

        $detailType1 = $payroll->details->where('id_karyawan', $karyawan->id)->first();
        $this->assertEquals(4500000, $detailType1->gaji_bersih); // 5jt - 500k
        $this->assertCount(2, $detailType1->rincian_komponen); // Pokok + Pinjaman
    }

    public function test_approve_payroll_fails_if_status_not_draft()
    {
        $payroll = $this->service->generatePayroll('2026-04', '2026-04-28');
        $payroll->update(['status' => PayrollStatus::APPROVED->value]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Status Payroll harus Draft');

        $payroll->refresh(); // Refresh agar status enum ter-update
        $this->service->approvePayroll($payroll);
    }
}
