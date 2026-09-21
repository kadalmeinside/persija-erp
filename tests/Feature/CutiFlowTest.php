<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\JenisCuti;
use App\Models\SaldoCuti;
use App\Models\PengajuanCuti;
use App\Models\ApprovalRule;
use App\Models\ApprovalProcess;

class CutiFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $pengajuUser;
    protected $approverUser;
    protected $hrUser;
    protected $jenisCuti;
    protected $karyawanPengaju;
    protected $karyawanApprover;

    protected function setUp(): void
    {
        parent::setUp();

        // Bypass Spatie Permission dan Authentication
        // tapi pertahankan SubstituteBindings agar route model binding bekerja
        // Bypass authentication, role, dan email verification middleware
        // agar test bisa fokus pada business logic (bukan auth flow)
        $this->withoutMiddleware([
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
            \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            \Spatie\Permission\Middleware\RoleMiddleware::class,
            \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);

        $dept = Departemen::create(['nama_departemen' => 'IT', 'kode_departemen' => 'IT']);

        $this->pengajuUser  = User::factory()->create(['email_verified_at' => now()]);
        $this->approverUser = User::factory()->create(['email_verified_at' => now()]);
        $this->hrUser       = User::factory()->create(['email_verified_at' => now()]);

        $this->karyawanPengaju = Karyawan::create([
            'user_id'              => $this->pengajuUser->id,
            'id_departemen'        => $dept->id,
            'nama_lengkap'         => 'Pengaju',
            'nomor_induk_karyawan' => '1001',
            'jenis_kelamin'        => 'L',
            'status_karyawan'      => 'Tetap',
            'jabatan'              => 'Staff IT',
        ]);

        $this->karyawanApprover = Karyawan::create([
            'user_id'              => $this->approverUser->id,
            'id_departemen'        => $dept->id,
            'nama_lengkap'         => 'Approver',
            'nomor_induk_karyawan' => '1002',
            'jenis_kelamin'        => 'L',
            'status_karyawan'      => 'Tetap',
            'jabatan'              => 'Manajer IT',
        ]);

        Karyawan::create([
            'user_id'              => $this->hrUser->id,
            'id_departemen'        => $dept->id,
            'nama_lengkap'         => 'HR User',
            'nomor_induk_karyawan' => '1003',
            'jenis_kelamin'        => 'P',
            'status_karyawan'      => 'Tetap',
            'jabatan'              => 'Staff HR',
        ]);

        $this->jenisCuti = JenisCuti::create([
            'nama_cuti'        => 'Cuti Tahunan',
            'kuota_default'    => 12,
            'is_active'        => true,
            'bisa_mundur'      => false,
            'khusus_perempuan' => false,
        ]);

        // Berikan saldo
        SaldoCuti::create([
            'id_karyawan'   => $this->karyawanPengaju->id,
            'id_jenis_cuti' => $this->jenisCuti->id,
            'tahun_periode' => date('Y'),
            'saldo_awal'    => 12,
            'saldo_terpakai'=> 0,
            'saldo_akhir'   => 12,
        ]);

        // Rule: Karyawan Approver adalah penyetuju
        ApprovalRule::create([
            'id_departemen'        => $dept->id,
            'tipe'                 => 'Cuti',
            'level_order'          => 1,
            'id_karyawan_approver' => $this->karyawanApprover->id,
            'label_aksi'           => 'Approval Manajer IT',
        ]);
    }

    /**
     * Menghitung N hari kerja berikutnya dari hari ini (skip weekend).
     * Agar test tidak flaky bergantung pada hari running.
     */
    private function nextWorkday(int $addDays = 1): \Carbon\Carbon
    {
        $date  = \Carbon\Carbon::today();
        $added = 0;
        while ($added < $addDays) {
            $date->addDay();
            if (!$date->isWeekend()) {
                $added++;
            }
        }
        return $date;
    }

    public function test_karyawan_bisa_mengajukan_cuti_dan_memotong_saldo_terpakai()
    {
        $tglMulai   = $this->nextWorkday(1)->format('Y-m-d');
        $tglSelesai = $this->nextWorkday(2)->format('Y-m-d');

        $payload = [
            'id_jenis_cuti' => $this->jenisCuti->id,
            'tgl_mulai'     => $tglMulai,
            'tgl_selesai'   => $tglSelesai,
            'alasan'        => 'Liburan',
        ];

        $response = $this->actingAs($this->pengajuUser)
            ->post(route('admin.cuti.store'), $payload);

        $response->assertRedirect(route('admin.cuti.my-requests'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tbl_pengajuan_cuti', [
            'id_jenis_cuti' => $this->jenisCuti->id,
            'jumlah_hari'   => 2,
            'status'        => 'Pending',
        ]);

        $saldo = SaldoCuti::where('id_karyawan', $this->karyawanPengaju->id)->first();
        $this->assertEquals(2, $saldo->saldo_terpakai);
        $this->assertEquals(10, $saldo->saldo_akhir);
    }

    public function test_approver_bisa_menyetujui_cuti()
    {
        $this->test_karyawan_bisa_mengajukan_cuti_dan_memotong_saldo_terpakai();

        $cuti = \App\Models\PengajuanCuti::first();

        $step = \App\Models\ApprovalProcess::where('id_cuti', $cuti->id)->where('status', 'Pending')->first();
        $this->assertNotNull($step, 'Tidak ada step approval Pending ditemukan.');
        $this->assertEquals(
            $this->karyawanApprover->id,
            $step->id_karyawan_target,
            "id_karyawan_target ({$step->id_karyawan_target}) != karyawanApprover->id ({$this->karyawanApprover->id})"
        );

        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->approverUser)
            ->post(route('admin.cuti.approve', $cuti->id), [
                'status'  => 'Approved',
                'catatan' => 'Silahkan liburan',
            ]);

        $response->assertRedirect();
        $this->assertFalse(
            $response->getSession()->has('error'),
            'Error: ' . $response->getSession()->get('error', 'none')
        );

        $cuti->refresh();
        $this->assertEquals('Approved', $cuti->status);
    }

    public function test_saldo_kembali_jika_cuti_ditolak()
    {
        $this->test_karyawan_bisa_mengajukan_cuti_dan_memotong_saldo_terpakai();

        $cuti = \App\Models\PengajuanCuti::first();

        $response = $this->actingAs($this->approverUser)
            ->post(route('admin.cuti.approve', $cuti->id), [
                'status'  => 'Rejected',
                'catatan' => 'Banyak kerjaan',
            ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');

        $cuti->refresh();
        $this->assertEquals('Rejected', $cuti->status);

        $saldo = SaldoCuti::where('id_karyawan', $this->karyawanPengaju->id)->first();
        $this->assertEquals(0, $saldo->saldo_terpakai);
        $this->assertEquals(12, $saldo->saldo_akhir);
    }

    public function test_gagal_cuti_jika_saldo_habis()
    {
        SaldoCuti::where('id_karyawan', $this->karyawanPengaju->id)->update([
            'saldo_terpakai' => 12,
            'saldo_akhir'    => 0,
        ]);

        $payload = [
            'id_jenis_cuti' => $this->jenisCuti->id,
            'tgl_mulai'     => $this->nextWorkday(1)->format('Y-m-d'),
            'tgl_selesai'   => $this->nextWorkday(10)->format('Y-m-d'),
            'alasan'        => 'Liburan',
        ];

        $response = $this->actingAs($this->pengajuUser)
            ->post(route('admin.cuti.store'), $payload);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('tbl_pengajuan_cuti', 0);
    }
}
