<?php

namespace App\Services;

use App\Models\BudgetMaster;
use App\Models\Karyawan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
        $namaBulanPacing = 'pacing_' . strtolower($tanggal->format('M')); // e.g., 'pacing_nov'

        if (isset($budget->$namaBulanPacing)) {
            $plafonBulanan = $budget->$namaBulanPacing;
            
            // TODO: Tambahkan logika pengecekan realisasi bulanan di sini jika perlu
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
}