<?php

namespace App\Services;

use App\Models\BudgetMaster;
use App\Models\Karyawan; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Class BudgetCheckService
 * Bertanggung jawab untuk semua logika validasi anggaran.
 */
class BudgetCheckService
{
    /**
     * Memeriksa satu baris item pengajuan terhadap anggaran yang tersedia.
     * (Method ini digunakan saat SUBMIT FORM UTAMA)
     *
     * @param int $id_departemen
     * @param int $id_akun
     * @param int $id_program
     * @param float $nominal
     * @param Carbon $tanggal
     * @return array
     */
    public function check(int $id_departemen, int $id_akun, int $id_program, float $nominal, Carbon $tanggal): array
    {
        $tahun = $tanggal->year;

        // 1. Cari baris anggaran yang sesuai
        $budget = BudgetMaster::where('tahun', $tahun)
            ->where('id_departemen', $id_departemen)
            ->where('id_akun', $id_akun)
            ->where('id_program', $id_program)
            ->first();

        // 2. Jika baris anggaran tidak ada
        if (!$budget) {
            Log::warning("Budget check failed: Anggaran tidak ditemukan", compact('tahun', 'id_departemen', 'id_akun', 'id_program'));
            return [
                'success' => false,
                'message' => 'Kombinasi anggaran (Dept/Akun/Program) tidak ditemukan di master anggaran.',
                'warning' => null,
            ];
        }

        // 3. Pengecekan Keras (Hard Check) - Plafon Tahunan
        // Gunakan lockForUpdate() untuk mencegah race condition
        $budget = BudgetMaster::where('id', $budget->id)->lockForUpdate()->first();
        
        $sisaTahunan = $budget->anggaran_total_tahun - $budget->anggaran_terikat_ytd - $budget->anggaran_realisasi_ytd;

        if ($nominal > $sisaTahunan) {
            Log::warning("Budget check failed: Plafon tahunan terlampaui", [
                'nominal' => $nominal, 'sisaTahunan' => $sisaTahunan, 'budget_id' => $budget->id
            ]);
            return [
                'success' => false,
                'message' => "Plafon tahunan terlampaui. Sisa: " . number_format($sisaTahunan, 2, ',', '.'),
                'warning' => null,
            ];
        }

        // 4. Pengecekan Lunak (Soft Check) - Pacing Bulanan
        $warningMessage = null;
        
        $monthSuffixMap = [
            1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr', 5 => 'mei', 6 => 'jun',
            7 => 'jul', 8 => 'agu', 9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'des'
        ];
        
        $monthIndex = (int)$tanggal->format('n');
        $namaBulanPacing = 'pacing_' . $monthSuffixMap[$monthIndex];

        if (isset($budget->$namaBulanPacing)) {
            $plafonBulanan = (float) $budget->$namaBulanPacing;
            
            if ($nominal > $plafonBulanan && $plafonBulanan > 0) {
                $warningMessage = "Peringatan: Pengajuan ini melebihi panduan pacing bulanan (Plafon: " . number_format($plafonBulanan, 2, ',', '.') . ")";
                Log::info("Budget check warning: Pacing bulanan terlampaui", [
                    'nominal' => $nominal, 'plafonBulanan' => $plafonBulanan, 'budget_id' => $budget->id
                ]);
            }
        }

        // 5. Sukses
        return [
            'success' => true,
            'message' => 'Anggaran tersedia.',
            'warning' => $warningMessage,
            'budget_id' => $budget->id, 
        ];
    }

    /**
     * Mengikat (commit) nominal ke anggaran_terikat_ytd.
     */
    public function commitBudget(int $budgetId, float $nominal): void
    {
        BudgetMaster::where('id', $budgetId)->increment('anggaran_terikat_ytd', $nominal);
        Log::info("Budget committed", ['budget_id' => $budgetId, 'nominal' => $nominal]);
    }

    /**
     * [BARU] Mengambil Sisa Saldo dari DB untuk pengecekan AJAX.
     *
     * @param int $id_departemen
     * @param int $id_akun
     * @param int $id_program
     * @param Carbon $tanggal
     * @return array
     */
    public function getSisaSaldoDB(int $id_departemen, int $id_akun, int $id_program, Carbon $tanggal): array
    {
        $tahun = $tanggal->year;

        $budget = BudgetMaster::where('tahun', $tahun)
            ->where('id_departemen', $id_departemen)
            ->where('id_akun', $id_akun)
            ->where('id_program', $id_program)
            ->first();

        if (!$budget) {
            return [
                'success' => false,
                'message' => 'Kombinasi anggaran tidak ditemukan.',
                'sisa_saldo_db' => 0
            ];
        }

        $sisaTahunan = $budget->anggaran_total_tahun - $budget->anggaran_terikat_ytd - $budget->anggaran_realisasi_ytd;
        
        return [
            'success' => true,
            'message' => 'Sisa saldo ditemukan.',
            'sisa_saldo_db' => $sisaTahunan
        ];
    }
}