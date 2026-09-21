<?php

namespace App\Services;

use App\Enums\AngsuranStatus;
use App\Models\Pinjaman;
use App\Models\AngsuranPinjaman;
use App\Models\PengajuanHeader;
use App\Models\PengajuanDetail;
use App\Models\AkunGl;
use App\Models\Karyawan;
use App\Models\ProgramKerja;
use App\Enums\PengajuanStatus;
use App\Enums\PaymentMethod;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PinjamanService
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }
    /**
     * Create a new Pinjaman and generate installment schedule.
     *
     * @param array $data
     * @return Pinjaman
     */
    public function createPinjaman(array $data): Pinjaman
    {
        return DB::transaction(function () use ($data) {
            $jumlahAngsuran = ceil($data['jumlah_pinjaman'] / $data['tenor_bulan']);

            $pinjaman = Pinjaman::create([
                'id_karyawan' => $data['id_karyawan'],
                'tanggal_pengajuan' => $data['tanggal_pengajuan'],
                'jumlah_pinjaman' => $data['jumlah_pinjaman'],
                'tenor_bulan' => $data['tenor_bulan'],
                'jumlah_angsuran_per_bulan' => $jumlahAngsuran,
                'keterangan' => $data['keterangan'] ?? null,
                'status' => PengajuanStatus::DRAFT->value
            ]);

            $this->generateInstallments($pinjaman, $data['tanggal_pengajuan'], $data['tenor_bulan'], $jumlahAngsuran);

            // 3. Initiate Approval Flow
            $this->approvalService->initApproval($pinjaman);

            return $pinjaman;
        });
    }

    /**
     * Generate installment records for a pinjaman.
     *
     * @param Pinjaman $pinjaman
     * @param string $startDate
     * @param int $tenor
     * @param float $amountPerMonth
     * @return void
     */
    private function generateInstallments(Pinjaman $pinjaman, string $startDate, int $tenor, float $amountPerMonth): void
    {
        $startMonth = Carbon::parse($startDate)->addMonth(); // First payment next month
        
        for ($i = 0; $i < $tenor; $i++) {
            AngsuranPinjaman::create([
                'id_pinjaman' => $pinjaman->id,
                'bulan_periode' => $startMonth->copy()->addMonths($i)->format('Y-m'),
                'jumlah_angsuran' => $amountPerMonth,
                'status_bayar' => AngsuranStatus::PENDING->value,
            ]);
        }
    }

    /**
     * Create a payment request (PengajuanHeader) for the approved pinjaman.
     * Called automatically by ApprovalService when the final approval step is reached.
     *
     * @param Pinjaman  $pinjaman
     * @param Karyawan|null $approverKaryawan  Karyawan yang melakukan approval (di-pass dari ApprovalService).
     *                                          Jika null, fallback ke departemen karyawan peminjam.
     * @return void
     */
    public function createPaymentRequest(Pinjaman $pinjaman, ?Karyawan $approverKaryawan = null): void
    {
        // 1. Determine GL Account for "Piutang Karyawan"
        // ENFORCE CONFIGURATION: Do not guess anymore.
        $receivableAccountId = \App\Models\Setting::where('key', 'account_receivable_employee')->value('value');
        
        if (!$receivableAccountId) {
            throw new \Exception("Konfigurasi Akun Piutang Karyawan belum diatur. Silakan atur di menu 'Finance & Accounting > Pengaturan Keuangan'.");
        }

        $receivableAccount = AkunGl::find($receivableAccountId);

        if (!$receivableAccount) {
             throw new \Exception("Akun Piutang Karyawan (ID: $receivableAccountId) tidak ditemukan di CoA.");
        }

        $accountId = $receivableAccount->id;

        // 2. Prepare Bank Info
        $karyawan = $pinjaman->karyawan;
        
        // Ensure Karyawan has primary_bank accessor or relation loaded if needed
        // The accessor getPrimaryBankAttribute exists in Karyawan model
        $primaryBank = $karyawan->primary_bank;

        $bankName = $primaryBank ? $primaryBank->nama_bank : '';
        $bankRek = $primaryBank ? $primaryBank->nomor_rekening : '';
        $bankAn = $primaryBank ? $primaryBank->atas_nama_rekening : '';

        // 3. Create Header of Payment Request
        // approverKaryawan di-pass dari ApprovalService (sudah resolve di sana, bukan di sini)
        // Fallback: gunakan departemen karyawan peminjam jika tidak ada approver context
        $approverDepartemenId = $approverKaryawan ? $approverKaryawan->id_departemen : $pinjaman->karyawan->id_departemen;

        $pengajuan = PengajuanHeader::create([
            'nomor_pengajuan' => DocumentNumberService::pinjaman(), // Sequential loan number
            'judul_pengajuan' => 'Pencairan Pinjaman: ' . $karyawan->nama_lengkap,
            'id_pengaju' => $approverKaryawan ? $approverKaryawan->id : $pinjaman->id_karyawan,
            
            // IMPORTANT: Use the Approver's Department so the Approval Flow follows HR/Finance rules
            'id_departemen' => $approverDepartemenId,
            
            'tgl_pengajuan' => now(),
            'tipe_pengajuan' => 'Langsung', // Direct Payment
            'total_nominal_diajukan' => $pinjaman->jumlah_pinjaman,
            
            // Set to PENDING APPROVAL (Not Approved yet)
            'status_global' => PengajuanStatus::PENDING_APPROVAL->value, 
            
            'catatan_header' => 'Auto-generated from Pinjaman ID: ' . $pinjaman->id . '. ' . $pinjaman->keterangan,
            'metode_pembayaran' => PaymentMethod::TRANSFER,
            'id_karyawan_penerima' => $karyawan->id,
            'bank_tujuan' => $bankName,
            'no_rek_tujuan' => $bankRek,
            'atas_nama_tujuan' => $bankAn
        ]);

        // 3.5 Determine Program Kerja (Try to find one in the Approver's Dept, e.g. HR Program)
        $program = ProgramKerja::where('id_departemen', $approverDepartemenId)->first();
        if (!$program) {
            // Fallback to Karyawan's Dept Program or First available
            $program = ProgramKerja::where('id_departemen', $karyawan->id_departemen)->first() ?? ProgramKerja::first();
        }
        $programId = $program ? $program->id : null;

        // 4. Create Detail
        PengajuanDetail::create([
            'id_pengajuan' => $pengajuan->id,
            'deskripsi_item' => 'Pinjaman Karyawan (Pokok)',
            'nominal_item' => $pinjaman->jumlah_pinjaman,
            'id_akun' => $accountId,
            'id_program' => $programId, 
        ]);

        // 5. Trigger Standard Approval Flow (Based on Approver's Dept Rules)
        $this->approvalService->initApproval($pengajuan);
    }

    /**
     * Get audit logs for a pinjaman and its installments.
     *
     * @param Pinjaman $pinjaman
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAuditLogs(Pinjaman $pinjaman)
    {
        $activityQuery = \Spatie\Activitylog\Models\Activity::with('causer')
            ->where(function ($query) use ($pinjaman) {
                $query->where('subject_type', 'App\Models\Pinjaman')
                      ->where('subject_id', $pinjaman->id);
            });

        $angsuranIds = $pinjaman->angsuran->pluck('id');
        if ($angsuranIds->isNotEmpty()) {
            $activityQuery->orWhere(function ($query) use ($angsuranIds) {
                $query->where('subject_type', 'App\Models\AngsuranPinjaman')
                      ->whereIn('subject_id', $angsuranIds);
            });
        }

        return $activityQuery->orderBy('created_at', 'desc')->get();
    }
}
