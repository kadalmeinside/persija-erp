<?php

namespace App\Services;

use App\Models\BudgetMaster;
use App\Models\BudgetDetail;
use App\Models\PeriodeAnggaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BudgetCheckService
{
    /**
     * Mengurangi saldo anggaran (Booking/Commit).
     * Status: Dana dipesan/ditahan, belum tentu keluar, tapi mengurangi jatah belanja.
     */
    public function commitBudget(int $budgetId, float $nominal): void
    {
        BudgetMaster::where('id', $budgetId)->increment('anggaran_terikat_ytd', $nominal);
    }

    /**
     * Mengembalikan saldo anggaran (Un-commit/Reversal).
     * Digunakan saat pengajuan dihapus, ditolak, atau saat Settlement (sebelum dicatat ulang).
     */
    public function unCommitBudget(int $budgetId, float $nominal): void
    {
        $budget = BudgetMaster::find($budgetId);
        if ($budget) {
            $budget->decrement('anggaran_terikat_ytd', $nominal);
        }
    }
    
    /**
     * [BARU] Memindahkan dari Terikat ke Realisasi.
     * Digunakan saat Finance memverifikasi laporan/pembayaran final.
     */
    public function realizeBudget(int $budgetId, float $nominal): void
    {
        $budget = BudgetMaster::find($budgetId);
        if ($budget) {
            // 1. Kurangi jatah Terikat (karena sudah bukan pesanan lagi)
            $budget->decrement('anggaran_terikat_ytd', $nominal);
            // 2. Tambahkan ke Realisasi (Uang benar-benar diakui hilang/terpakai)
            $budget->increment('anggaran_realisasi_ytd', $nominal);
        }
    }

    /**
     * Helper mencari ID Budget Master berdasarkan parameter.
     */
    /**
     * Helper mencari ID Budget Master berdasarkan parameter.
     */
    public function getBudgetId(int $id_departemen, int $id_akun, int $id_program, Carbon $tanggal): ?int
    {
        // Cari Periode yang mencakup tanggal
        $periode = PeriodeAnggaran::whereDate('tanggal_mulai', '<=', $tanggal)
                    ->whereDate('tanggal_selesai', '>=', $tanggal)
                    ->first();

        if (!$periode) return null;

        // Cari Pos Anggaran (Mapping Program <-> Akun)
        // Note: id_departemen is ignored for finding the budget master itself in Centralized Budgeting,
        // unless we implement delegation logic later. For now, budget is at Program level.
        $posAnggaran = \App\Models\PosAnggaran::where('id_program_kerja', $id_program)
            ->where('id_akun_gl', $id_akun)
            ->where('is_active', true)
            ->first();

        if (!$posAnggaran) return null;

        $budget = BudgetMaster::where('id_periode_anggaran', $periode->id)
            ->where('id_pos_anggaran', $posAnggaran->id)
            ->first();

        return $budget ? $budget->id : null;
    }

    /**
     * Mendapatkan informasi sisa saldo (Tahunan).
     */
    public function getSisaSaldoDB(int $id_departemen, int $id_akun, int $id_program, Carbon $tanggal): array
    {
        // 0. Cek Tipe Akun (Bypass untuk Utang/Liability)
        $akun = \App\Models\AkunGl::find($id_akun);
        if ($akun && in_array($akun->tipe_akun, ['Utang', 'Kewajiban'])) {
             return [
                'success' => true,
                'message' => 'Akun Kewajiban (Non-Budgeted).',
                'sisa_saldo_db' => 999999999999, // Unlimited
                'budget_id' => null,
            ];
        }

        $budgetId = $this->getBudgetId($id_departemen, $id_akun, $id_program, $tanggal);

        if (!$budgetId) {
            return ['success' => false, 'message' => 'Anggaran tidak ditemukan pada periode ini.', 'sisa_saldo_db' => 0];
        }

        $budget = BudgetMaster::find($budgetId);
        
        // Rumus Sisa: Total - (Terikat + Realisasi)
        $sisaTahunan = $budget->anggaran_total_tahun - $budget->anggaran_terikat_ytd - $budget->anggaran_realisasi_ytd;
        
        return [
            'success' => true,
            'message' => 'Sisa saldo ditemukan.',
            'sisa_saldo_db' => $sisaTahunan,
            'budget_id' => $budget->id,
        ];
    }

    /**
     * Melakukan pengecekan ketersediaan dana (Check).
     */
    public function check(int $id_departemen, int $id_akun, int $id_program, float $nominal, Carbon $tanggal): array
    {
        // 0. Cek Tipe Akun (Bypass untuk Utang/Liability)
        $akun = \App\Models\AkunGl::find($id_akun);
        if ($akun && in_array($akun->tipe_akun, ['Utang', 'Kewajiban'])) {
             return [
                'success' => true,
                'message' => 'Transaksi Non-Budgeter (Kewajiban).',
                'warning' => null,
                'budget_id' => null,
            ];
        }

        // 1. Cek Saldo Tahunan (Hard Limit)
        $saldoResult = $this->getSisaSaldoDB($id_departemen, $id_akun, $id_program, $tanggal);
        
        if (!$saldoResult['success']) {
            return [ 'success' => false, 'message' => $saldoResult['message'], 'warning' => null ];
        }

        $budgetId = $saldoResult['budget_id'];
        $sisaTahunan = $saldoResult['sisa_saldo_db'];

        if ($nominal > $sisaTahunan) {
            return [
                'success' => false,
                'message' => "Plafon tahunan terlampaui. Sisa: " . number_format($sisaTahunan, 0, ',', '.'),
                'warning' => null,
            ];
        }

        // 2. Cek Pacing Bulanan (Soft Limit - Warning Only)
        $warningMessage = null;
        $pacingDetail = BudgetDetail::where('id_budget_master', $budgetId)
            ->where('bulan', $tanggal->month)
            ->where('tahun', $tanggal->year)
            ->first();

        if ($pacingDetail) {
            $plafonBulanan = (float) $pacingDetail->nominal_pacing;
            if ($plafonBulanan > 0 && $nominal > $plafonBulanan) {
                $warningMessage = "Over Budget Bulanan (Plafon: " . number_format($plafonBulanan, 0, ',', '.') . ").";
            }
        }

        return [
            'success' => true,
            'message' => 'Anggaran tersedia.',
            'warning' => $warningMessage,
            'budget_id' => $budgetId, 
        ];
    }
}