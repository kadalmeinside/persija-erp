<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\JenisCuti;
use App\Models\SaldoCuti;
use App\Models\PengajuanCuti;
use App\Models\PengajuanHeader;
use App\Models\Pinjaman;
use App\Models\InvoiceHeader;
use App\Models\ApprovalRule;
use App\Models\ApprovalProcess;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedApprovalCenterTest extends TestCase
{
    use RefreshDatabase;

    protected $pengajuUser;
    protected $approverUser;
    protected $karyawanPengaju;
    protected $karyawanApprover;
    protected $departemen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
            \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            \Spatie\Permission\Middleware\RoleMiddleware::class,
            \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);

        $this->departemen = Departemen::create([
            'nama_departemen' => 'Finance', 
            'kode_departemen' => 'FIN'
        ]);

        $this->pengajuUser = User::factory()->create();
        $this->approverUser = User::factory()->create();

        $this->karyawanPengaju = Karyawan::create([
            'user_id' => $this->pengajuUser->id,
            'id_departemen' => $this->departemen->id,
            'nama_lengkap' => 'Staff Biasa',
            'nomor_induk_karyawan' => '1001',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staff',
        ]);

        $this->karyawanApprover = Karyawan::create([
            'user_id' => $this->approverUser->id,
            'id_departemen' => $this->departemen->id,
            'nama_lengkap' => 'Direktur Utama',
            'nomor_induk_karyawan' => '1002',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Direktur',
        ]);

        // Finance Settings
        $akunGL = \App\Models\AkunGl::create([
            'kode_akun' => '1001',
            'nama_akun' => 'Kas Besar',
            'tipe_akun' => 'Aset',
            'saldo_sekarang' => 100000000
        ]);
        $akunPiutang = \App\Models\AkunGl::create([
            'kode_akun' => '1100',
            'nama_akun' => 'Piutang Usaha',
            'tipe_akun' => 'Aset',
            'saldo_sekarang' => 0
        ]);
        \App\Models\Setting::create(['key' => 'account_receivable_employee', 'value' => $akunPiutang->id]);
        \App\Models\Setting::create(['key' => 'account_bank_utama', 'value' => $akunGL->id]);

        $programKerja = \App\Models\ProgramKerja::create(['nama_program' => 'Kasbon Karyawan', 'id_departemen' => $this->departemen->id, 'tahun' => '2026', 'status' => 'Aktif']);
        \App\Models\Setting::create(['key' => 'default_program_loans', 'value' => (string) $programKerja->id]);

        // Setup 4 jenis rule approval ke 1 Approver yang sama
        $types = ['Cuti', 'Pengajuan', 'Pinjaman', 'Invoice'];
        foreach ($types as $type) {
            ApprovalRule::create([
                'id_departemen' => $this->departemen->id,
                'id_karyawan_approver' => $this->karyawanApprover->id,
                'tipe' => $type,
                'level_order' => 1,
                'label_aksi' => 'Approve by Director',
                'min_amount' => 0
            ]);
        }
    }

    public function test_approver_can_see_and_approve_all_types_of_documents(): void
    {
        $service = app(ApprovalService::class);

        // 1. Buat Cuti
        $jenisCuti = JenisCuti::create(['nama_cuti' => 'Tahunan', 'kuota_default' => 12]);
        SaldoCuti::create([
            'id_karyawan' => $this->karyawanPengaju->id,
            'id_jenis_cuti' => $jenisCuti->id,
            'tahun_periode' => date('Y'),
            'saldo_awal' => 12,
            'saldo_terpakai' => 0,
            'saldo_akhir' => 12,
        ]);
        $cuti = PengajuanCuti::create([
            'id_karyawan' => $this->karyawanPengaju->id,
            'id_jenis_cuti' => $jenisCuti->id,
            'tgl_mulai' => now()->addDays(1)->format('Y-m-d'),
            'tgl_selesai' => now()->addDays(2)->format('Y-m-d'),
            'jumlah_hari' => 2,
            'alasan' => 'Liburan',
            'status' => 'Pending'
        ]);
        $service->initApproval($cuti);

        // 2. Buat Pengajuan (Dana)
        $pengajuan = PengajuanHeader::create([
            'id_pengaju' => $this->karyawanPengaju->id,
            'id_departemen' => $this->departemen->id,
            'nomor_pengajuan' => 'REQ-001',
            'tgl_pengajuan' => now(),
            'tipe_pengajuan' => 'Reimburse',
            'judul_pengajuan' => 'Beli Laptop',
            'total_nominal_diajukan' => 1000000,
            'status_global' => 'Draft'
        ]);
        $service->initApproval($pengajuan);

        // 3. Buat Pinjaman
        $pinjaman = Pinjaman::create([
            'id_karyawan' => $this->karyawanPengaju->id,
            'tanggal_pengajuan' => now(),
            'jumlah_pinjaman' => 5000000,
            'tenor_bulan' => 10,
            'jumlah_angsuran_per_bulan' => 500000,
            'alasan_pinjaman' => 'Renovasi',
            'status' => 'Draft'
        ]);
        $service->initApproval($pinjaman);

        // 4. Buat Invoice
        $pelanggan = \App\Models\Pelanggan::create([
            'kode_pelanggan' => 'CUST-001',
            'nama_pelanggan' => 'PT Makmur'
        ]);
        $invoice = \App\Models\InvoiceHeader::create([
            'nomor_invoice' => 'INV-2026-001',
            'id_pelanggan' => $pelanggan->id,
            'tgl_invoice' => now(),
            'tgl_jatuh_tempo' => now()->addDays(30),
            'total_tagihan' => 10000000,
            'sisa_tagihan' => 10000000,
            'ppn_amount' => 0,
            'pph_amount' => 0,
            'catatan' => 'Pembayaran Server',
            'status' => 'Draft',
            'id_departemen' => $this->departemen->id,
            'created_by' => $this->karyawanPengaju->user_id ?? 1
        ]);
        \App\Models\InvoiceDetail::create([
            'id_invoice' => $invoice->id,
            'deskripsi_item' => 'Server Cloud',
            'kuantitas' => 1,
            'harga_satuan' => 10000000,
            'total_harga' => 10000000,
            'id_akun_pendapatan' => \App\Models\AkunGl::first()->id
        ]);
        $service->initApproval($invoice);

        // VERIFY INIT SUCCESS (Ada 4 proses approval pending)
        $pendingApprovals = ApprovalProcess::where('id_karyawan_target', $this->karyawanApprover->id)
            ->where('status', 'Pending')
            ->count();
        
        $this->assertEquals(4, $pendingApprovals, 'Harus ada 4 dokumen yang menunggu persetujuan Direktur.');

        // 5. Approver memproses semuanya secara berurutan
        // Approve Cuti
        $processCuti = ApprovalProcess::forDocument('Cuti', $cuti->id)->first();
        $this->assertNotNull($processCuti);
        $service->approve($cuti, $this->karyawanApprover->id, 'Cuti ACC');
        $this->assertEquals('Approved', $cuti->fresh()->status);

        // Approve Pengajuan
        $processPengajuan = ApprovalProcess::forDocument('Pengajuan', $pengajuan->id)->first();
        $this->assertNotNull($processPengajuan);
        $service->approve($pengajuan, $this->karyawanApprover->id, 'Pengajuan ACC');
        $this->assertEquals('Approved', $pengajuan->fresh()->status_global->value ?? $pengajuan->fresh()->status_global);

        // Approve Pinjaman
        $processPinjaman = ApprovalProcess::forDocument('Pinjaman', $pinjaman->id)->first();
        $this->assertNotNull($processPinjaman);
        $service->approve($pinjaman, $this->karyawanApprover->id, 'Pinjaman ACC');
        $this->assertEquals('Approved', $pinjaman->fresh()->status);

        // Approve Invoice
        $processInvoice = ApprovalProcess::forDocument('Invoice', $invoice->id)->first();
        $this->assertNotNull($processInvoice);
        $service->approve($invoice, $this->karyawanApprover->id, 'Invoice ACC');
        $this->assertEquals('Unpaid', $invoice->fresh()->status->value ?? $invoice->fresh()->status);

        // Pastikan queue kosong
        $remainingPending = ApprovalProcess::where('id_karyawan_target', $this->karyawanApprover->id)
            ->where('status', 'Pending')
            ->count();
        
        $this->assertEquals(0, $remainingPending, 'Semua dokumen seharusnya sudah diproses.');
    }
}
