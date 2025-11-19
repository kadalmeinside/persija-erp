<?php

namespace App\Services;

use App\Models\BudgetMaster;
use App\Models\Karyawan; // Meskipun tidak digunakan di 'check', ini mungkin diperlukan untuk logika masa depan
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
        // PERBAIKAN PENTING: Gunakan lockForUpdate() untuk mencegah race condition
        // Kita perlu query ulang dengan lock
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
        
        // --- PERBAIKAN LOGIKA NAMA BULAN ---
        // Kita tidak bisa menggunakan $tanggal->format('M') karena tidak cocok (aug vs agu)
        // Kita gunakan mapping manual berdasarkan nomor bulan
        $monthSuffixMap = [
            1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr', 5 => 'mei', 6 => 'jun',
            7 => 'jul', 8 => 'agu', 9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'des'
        ];
        
        $monthIndex = (int)$tanggal->format('n'); // Dapatkan nomor bulan (1-12)
        $namaBulanPacing = 'pacing_' . $monthSuffixMap[$monthIndex];
        // --- AKHIR PERBAIKAN ---

        if (isset($budget->$namaBulanPacing)) {
            $plafonBulanan = (float) $budget->$namaBulanPacing;
            
            // TODO: Tambahkan logika pengecekan realisasi bulanan di sini jika perlu
            // (misal: $totalSudahDigunakanBulanIni + $nominal > $plafonBulanan)
            
            // Untuk saat ini, kita hanya cek jika 1 transaksi > plafon bulanan
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
            'budget_id' => $budget->id, // Kirim ID budget untuk di-update
        ];
    }

    /**
     * Mengikat (commit) nominal ke anggaran_terikat_ytd.
     * Fungsi ini harus dipanggil di dalam DB::transaction() di Controller.
     *
     * @param int $budgetId
     * @param float $nominal
     * @return void
     */
    public function commitBudget(int $budgetId, float $nominal): void
    {
        // Gunakan increment untuk operasi atomik yang aman
        BudgetMaster::where('id', $budgetId)->increment('anggaran_terikat_ytd', $nominal);
        Log::info("Budget committed", ['budget_id' => $budgetId, 'nominal' => $nominal]);
    }
}