<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PengajuanStatus;
use App\Enums\PengajuanType;
use App\Models\KasBank;
use App\Models\PengajuanHeader;
use App\Models\PengajuanDetail;
use App\Models\Vendor;
use App\Models\Karyawan;
use App\Models\TaxType;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PengajuanService
{
    protected $budgetCheckService;
    protected $approvalService;
    protected $calculationService;

    public function __construct(
        BudgetCheckService $budgetCheckService,
        ApprovalService $approvalService,
        CalculationService $calculationService
    ) {
        $this->budgetCheckService = $budgetCheckService;
        $this->approvalService = $approvalService;
        $this->calculationService = $calculationService;
    }

    /**
     * Handle creation of Pengajuan.
     */
    public function create(array $data, $attachmentFile = null)
    {
        return DB::transaction(function () use ($data, $attachmentFile) {
            $warnings = [];
            $tanggal = Carbon::parse($data['tgl_pengajuan']);
            $tipe = $data['tipe_pengajuan'] ?? null;

            // *** PETTY CASH: validasi saldo Kas Kecil sebelum lanjut ***
            if ($tipe === PengajuanType::PETTY_CASH->value) {
                $this->validatePettyCashBalance(
                    $data['id_kas_kecil'] ?? null,
                    $data['total_nominal_diajukan']
                );
            }

            // 1. Upload File
            $path = null;
            if ($attachmentFile) {
                $path = $attachmentFile->store('attachments', 'public');
            }

            // 2. Resolve Bank Info (tidak berlaku untuk Petty Cash)
            $bankInfo = ($tipe === PengajuanType::PETTY_CASH->value)
                ? ['bank_tujuan' => null, 'no_rek_tujuan' => null, 'atas_nama_tujuan' => null]
                : $this->resolveBankInfo($data);

            // 3. Create Header
            $pengajuanHeader = PengajuanHeader::create([
                'nomor_pengajuan'       => DocumentNumberService::pengajuan(),
                'judul_pengajuan'       => $data['judul_pengajuan'],
                'id_pengaju'            => $data['id_pengaju'],
                'id_departemen'         => $data['id_departemen'],
                'tgl_pengajuan'         => $tanggal,
                'tipe_pengajuan'        => $tipe,
                'total_nominal_diajukan'=> $data['total_nominal_diajukan'],
                'status_global'         => PengajuanStatus::PENDING_APPROVAL->value,
                'catatan_header'        => $data['catatan_header'] ?? null,
                'attachment_path'       => $path,
                'metode_pembayaran'     => $data['metode_pembayaran'] ?? null,
                'id_vendor_penerima'    => $data['id_vendor_penerima'] ?? null,
                'id_karyawan_penerima'  => $data['id_karyawan_penerima'] ?? null,
                'bank_tujuan'           => $bankInfo['bank_tujuan'],
                'no_rek_tujuan'         => $bankInfo['no_rek_tujuan'],
                'atas_nama_tujuan'      => $bankInfo['atas_nama_tujuan'],
                'id_kas_kecil'          => ($tipe === PengajuanType::PETTY_CASH->value) ? ($data['id_kas_kecil'] ?? null) : null,
            ]);

            // 4. Process Items (Budget & Tax)
            foreach ($data['items'] as $item) {
                $this->processItem($pengajuanHeader, $item, $data['id_departemen'], $tanggal, $warnings);
            }

            // 5. Init Approval
            $this->approvalService->initApproval($pengajuanHeader);

            return ['pengajuan' => $pengajuanHeader, 'warnings' => $warnings];
        });
    }

    /**
     * Handle update of Pengajuan.
     */
    public function update(PengajuanHeader $pengajuan, array $data, $attachmentFile = null)
    {
        return DB::transaction(function () use ($pengajuan, $data, $attachmentFile) {
            $warnings = [];
            $tanggal = Carbon::parse($data['tgl_pengajuan']);

            // 1. Reversal Old Budget
            $this->reversalBudget($pengajuan);
            $pengajuan->detail()->delete();

            // 2. File Update
            $path = $pengajuan->attachment_path;
            if ($attachmentFile) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                $path = $attachmentFile->store('attachments', 'public');
            }

            // 3. Resolve Bank Info (Prioritize new input, fallback to old if not provided but logic says we should re-fetch if target changed)
            // Ideally, the controller passes full data. Let's re-resolve based on current input.
            // If input is empty, we might want to keep old? But here we assume data contains current state.
            // Actually, for update, we should re-resolve if target changed.
            $bankInfo = $this->resolveBankInfo($data, $pengajuan);

            // 4. Update Header
            $pengajuan->update([
                'judul_pengajuan' => $data['judul_pengajuan'],
                'tgl_pengajuan' => $tanggal,
                'tipe_pengajuan' => $data['tipe_pengajuan'],
                'total_nominal_diajukan' => $data['total_nominal_diajukan'],
                'catatan_header' => $data['catatan_header'] ?? null,
                'attachment_path' => $path,
                'metode_pembayaran' => $data['metode_pembayaran'],
                'id_vendor_penerima' => $data['id_vendor_penerima'] ?? null,
                'id_karyawan_penerima' => $data['id_karyawan_penerima'] ?? null,
                'bank_tujuan' => $bankInfo['bank_tujuan'],
                'no_rek_tujuan' => $bankInfo['no_rek_tujuan'],
                'atas_nama_tujuan' => $bankInfo['atas_nama_tujuan'],
            ]);

            // 5. Process Items
            foreach ($data['items'] as $item) {
                $this->processItem($pengajuan, $item, $data['id_departemen'], $tanggal, $warnings);
            }

            return ['pengajuan' => $pengajuan, 'warnings' => $warnings];
        });
    }

    private function processItem($header, $item, $deptId, $date, &$warnings)
    {
        // Budget Check
        $budgetCheck = $this->budgetCheckService->check(
            $deptId,
            $item['id_akun'],
            $item['id_program'],
            $item['nominal_item'],
            $date
        );

        if (!$budgetCheck['success']) {
            throw new \Exception("Budget Error ({$item['deskripsi_item']}): " . $budgetCheck['message']);
        }
        if ($budgetCheck['warning']) {
            $warnings[] = $item['deskripsi_item'] . ": " . $budgetCheck['warning'];
        }

        // Tax Calculation (Centralized)
        $taxAmount = 0;
        $taxRate = 0;
        
        if (!empty($item['id_tax_type'])) {
            $taxType = TaxType::find($item['id_tax_type']);
            if ($taxType) {
                // Call Service
                $calcResult = $this->calculationService->calculateTax(
                    (float) $item['nominal_item'], 
                    (float) $taxType->rate, 
                    $taxType->tipe
                );
                
                $taxRate = $calcResult['tax_rate'];
                $taxAmount = $calcResult['tax_amount'];
            }
        }

        // Create Detail
        $header->detail()->create([
            'deskripsi_item' => $item['deskripsi_item'],
            'nominal_item' => $item['nominal_item'],
            'id_program' => $item['id_program'],
            'id_akun' => $item['id_akun'],
            'id_tax_type' => $item['id_tax_type'] ?? null,
            'rate_pajak' => $taxRate,
            'nominal_pajak' => $taxAmount,
        ]);

        // Commit Budget (hanya jika budget ditemukan — bypassed untuk akun Kewajiban dll.)
        if (!empty($budgetCheck['budget_id'])) {
            $this->budgetCheckService->commitBudget($budgetCheck['budget_id'], $item['nominal_item']);
        }
    }

    private function reversalBudget($pengajuan)
    {
        $oldDate = Carbon::parse($pengajuan->tgl_pengajuan);
        foreach ($pengajuan->detail as $oldItem) {
            $oldBudgetId = $this->budgetCheckService->getBudgetId(
                $pengajuan->id_departemen, 
                $oldItem->id_akun, 
                $oldItem->id_program, 
                $oldDate
            );
            if ($oldBudgetId) {
                $this->budgetCheckService->unCommitBudget($oldBudgetId, $oldItem->nominal_item);
            }
        }
    }

    private function resolveBankInfo($data, $existingHeader = null)
    {
        $bankTujuan = $data['bank_tujuan'] ?? ($existingHeader->bank_tujuan ?? null);
        $noRekTujuan = $data['no_rek_tujuan'] ?? ($existingHeader->no_rek_tujuan ?? null);
        $anTujuan = $data['atas_nama_tujuan'] ?? ($existingHeader->atas_nama_tujuan ?? null);

        if (isset($data['metode_pembayaran']) && $data['metode_pembayaran'] == PaymentMethod::TRANSFER->value) {
            $idVendor = $data['id_vendor_penerima'] ?? null;
            $idKaryawan = $data['id_karyawan_penerima'] ?? null;

            // If target changed or explicit nulls, re-fetch
            $shouldFetch = true;
            if ($existingHeader) {
                // If IDs match existing, and we have existing bank info, maybe keep it unless overridden?
                // But for simplicity and correctness, if IDs are provided, we fetch master data if manual fields are empty.
            }

            if ($idVendor) {
                $vendor = Vendor::with('rekeningBank')->find($idVendor);
                if ($vendor && $vendor->primary_bank) {
                    // Only override if manual input is empty OR if we want to enforce master data
                    // Let's assume if manual input is empty, use master.
                    if (empty($data['bank_tujuan'])) $bankTujuan = $vendor->primary_bank->nama_bank;
                    if (empty($data['no_rek_tujuan'])) $noRekTujuan = $vendor->primary_bank->nomor_rekening;
                    if (empty($data['atas_nama_tujuan'])) $anTujuan = $vendor->primary_bank->atas_nama_rekening;
                }
            } elseif ($idKaryawan) {
                $karyawan = Karyawan::with('rekeningBank')->find($idKaryawan);
                $bank = $karyawan->primary_bank;
                if ($bank) {
                    if (empty($data['bank_tujuan'])) $bankTujuan = $bank->nama_bank;
                    if (empty($data['no_rek_tujuan'])) $noRekTujuan = $bank->nomor_rekening;
                    if (empty($data['atas_nama_tujuan'])) $anTujuan = $bank->atas_nama_rekening;
                }
            }
        } else {
            // Cash
            $bankTujuan = null;
            $noRekTujuan = null;
            $anTujuan = null;
        }

        return [
            'bank_tujuan'     => $bankTujuan,
            'no_rek_tujuan'   => $noRekTujuan,
            'atas_nama_tujuan'=> $anTujuan,
        ];
    }

    // =========================================================================
    // DESTROY (Hapus Permanen)
    // =========================================================================

    /**
     * Hapus pengajuan secara permanen.
     * Mengembalikan budget, menghapus file, dan semua data terkait.
     */
    public function destroyPengajuan(PengajuanHeader $pengajuan): void
    {
        DB::transaction(function () use ($pengajuan) {
            $this->uncommitBudgetForDetails($pengajuan);

            if ($pengajuan->attachment_path && Storage::disk('public')->exists($pengajuan->attachment_path)) {
                Storage::disk('public')->delete($pengajuan->attachment_path);
            }

            $pengajuan->detail()->delete();
            $pengajuan->approvalProcess()->delete();
            $pengajuan->delete();
        });
    }

    // =========================================================================
    // CANCEL (Void — tetap simpan record)
    // =========================================================================

    /**
     * Batalkan pengajuan (Void). Budget dikembalikan, record tetap ada.
     */
    public function cancelPengajuan(PengajuanHeader $pengajuan, string $reason, int $cancelledBy): void
    {
        DB::transaction(function () use ($pengajuan, $reason, $cancelledBy) {
            $this->uncommitBudgetForDetails($pengajuan);

            $user = \App\Models\User::find($cancelledBy);
            $pengajuan->update([
                'status_global'  => PengajuanStatus::CANCELLED,
                'catatan_header' => $pengajuan->catatan_header
                    . " [DIBATALKAN oleh {$user->name}: {$reason}]",
            ]);

            // Batalkan langkah approval yang masih Pending
            $pengajuan->approvalProcess()
                ->where('status', 'Pending')
                ->update([
                    'status'  => 'Rejected',
                    'catatan' => 'Auto-Rejected by System (Cancellation)',
                ]);
        });
    }

    // =========================================================================
    // PRIVATE: Budget uncommit helper
    // =========================================================================

    private function uncommitBudgetForDetails(PengajuanHeader $pengajuan): void
    {
        if (!$pengajuan->relationLoaded('detail')) {
            $pengajuan->load('detail');
        }

        $tgl = Carbon::parse($pengajuan->tgl_pengajuan);
        foreach ($pengajuan->detail as $item) {
            $budgetId = $this->budgetCheckService->getBudgetId(
                $pengajuan->id_departemen,
                $item->id_akun,
                $item->id_program,
                $tgl
            );
            if ($budgetId) {
                $this->budgetCheckService->unCommitBudget($budgetId, $item->nominal_item);
            }
        }
    }

    // =========================================================================
    // PETTY CASH: Validasi Saldo & GL Posting
    // =========================================================================

    /**
     * Validasi saldo Kas Kecil sebelum pengajuan dibuat.
     * Menghitung saldo real-time: saldo_awal + debit GL - kredit GL.
     *
     * @throws \Exception jika saldo tidak mencukupi atau KasBank tidak ditemukan
     */
    private function validatePettyCashBalance(?int $kasKecilId, float $nominal): void
    {
        if (!$kasKecilId) {
            throw new \Exception('Akun Kas Kecil wajib dipilih untuk pengajuan Petty Cash.');
        }

        $kasBank = KasBank::with('akunGl')->findOrFail($kasKecilId);

        if (!$kasBank->id_akun_gl) {
            throw new \Exception("Kas Kecil '{$kasBank->nama_bank}' belum terhubung ke Akun GL.");
        }

        $saldoReal = $this->getPettyCashBalance($kasBank);

        if ($saldoReal < $nominal) {
            throw new \Exception(
                "Saldo Kas Kecil '{$kasBank->nama_bank}' tidak mencukupi. " .
                "Saldo saat ini: Rp " . number_format($saldoReal, 0, ',', '.') . ". " .
                "Lakukan replenishment (Internal Transfer) terlebih dahulu."
            );
        }
    }

    /**
     * Hitung saldo real-time Kas Kecil dari jurnal GL.
     * Saldo = saldo_awal + total_kredit_masuk - total_debit_keluar dari akun GL terkait.
     *
     * Catatan: Untuk Kas (Aset), debit = masuk, kredit = keluar.
     */
    public function getPettyCashBalance(KasBank $kasBank): float
    {
        $saldoAwal = (float) $kasBank->saldo_awal;

        $movements = \App\Models\JurnalDetail::where('id_akun', $kasBank->id_akun_gl)
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(kredit), 0) as total_kredit')
            ->first();

        // Kas/Aset: saldo = saldo_awal + debit - kredit
        return $saldoAwal + (float) $movements->total_debit - (float) $movements->total_kredit;
    }

    /**
     * Posting GL untuk Petty Cash setelah final approval.
     * Dipanggil oleh ApprovalService::finalizeApproval().
     *
     * Jurnal:
     *   Dr. Akun Beban (per item)    Rp X
     *       Cr. Kas Kecil (KasBank)  Rp X
     */
    public function postPettyCashGL(PengajuanHeader $pengajuan): void
    {
        DB::transaction(function () use ($pengajuan) {
            $pengajuan->load(['detail', 'kasKecil.akunGl']);

            $kasBank = $pengajuan->kasKecil;
            if (!$kasBank || !$kasBank->id_akun_gl) {
                throw new \Exception('Kas Kecil atau Akun GL tidak ditemukan untuk Petty Cash ini.');
            }

            $glDetails = [];
            $totalNominal = 0;

            foreach ($pengajuan->detail as $item) {
                $glDetails[] = [
                    'id_akun'    => $item->id_akun,
                    'debit'      => $item->nominal_item,
                    'kredit'     => 0,
                    'keterangan' => $item->deskripsi_item,
                ];
                $totalNominal += $item->nominal_item;
            }

            // Credit Kas Kecil (total)
            $glDetails[] = [
                'id_akun'    => $kasBank->id_akun_gl,
                'debit'      => 0,
                'kredit'     => $totalNominal,
                'keterangan' => 'Pengeluaran Petty Cash: ' . $pengajuan->judul_pengajuan,
            ];

            GLService::createJournal(
                $pengajuan->tgl_pengajuan,
                'Petty Cash: ' . $pengajuan->nomor_pengajuan . ' - ' . $pengajuan->judul_pengajuan,
                $glDetails,
                'PC',
                $pengajuan->id,
                'PettyCash'
            );

            // Update status → PAID (uang sudah keluar secara fisik)
            $pengajuan->update(['status_global' => PengajuanStatus::PAID]);
        });
    }
}
