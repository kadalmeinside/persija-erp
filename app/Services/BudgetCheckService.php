<?php

namespace App\Services;

use App\Models\BudgetMaster;
use App\Models\BudgetDetail;
use App\Models\PeriodeAnggaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BudgetCheckService
{
    public function commitBudget(int $budgetId, float $nominal): void
    {
        // Commit tetap ke Header (Agregat Tahunan)
        BudgetMaster::where('id', $budgetId)->increment('anggaran_terikat_ytd', $nominal);
        Log::info("Budget committed", ['budget_id' => $budgetId, 'nominal' => $nominal]);
    }

    public function getSisaSaldoDB(int $id_departemen, int $id_akun, int $id_program, Carbon $tanggal): array
    {
        // 1. Cari Periode Aktif
        $periode = PeriodeAnggaran::where('is_active', true)->first();
        
        if (!$periode) {
             // Fallback: Coba cari periode berdasarkan tanggal pengajuan
             $periode = PeriodeAnggaran::whereDate('tanggal_mulai', '<=', $tanggal)
                        ->whereDate('tanggal_selesai', '>=', $tanggal)
                        ->first();
        }

        if (!$periode) {
            return [ 'success' => false, 'message' => 'Tidak ada periode anggaran yang cocok.', 'sisa_saldo_db' => 0 ];
        }

        // 2. Cari Budget Header
        $budget = BudgetMaster::where('id_periode_anggaran', $periode->id)
            ->where('id_departemen', $id_departemen)
            ->where('id_akun', $id_akun)
            ->where('id_program', $id_program)
            ->first();

        if (!$budget) {
            return [ 'success' => false, 'message' => 'Anggaran tidak ditemukan.', 'sisa_saldo_db' => 0 ];
        }

        $sisaTahunan = $budget->anggaran_total_tahun - $budget->anggaran_terikat_ytd - $budget->anggaran_realisasi_ytd;
        
        return [
            'success' => true,
            'message' => 'Sisa saldo ditemukan.',
            'sisa_saldo_db' => $sisaTahunan,
            'budget_id' => $budget->id,
        ];
    }

    public function check(int $id_departemen, int $id_akun, int $id_program, float $nominal, Carbon $tanggal): array
    {
        // 1. Cek Saldo Tahunan (Hard Limit)
        $saldoResult = $this->getSisaSaldoDB($id_departemen, $id_akun, $id_program, $tanggal);
        
        if (!$saldoResult['success']) {
            return [ 'success' => false, 'message' => $saldoResult['message'], 'warning' => null ];
        }

        $budgetId = $saldoResult['budget_id'];
        $sisaTahunan = $saldoResult['sisa_saldo_db'];

        // Lock row untuk konkurensi
        $budget = BudgetMaster::where('id', $budgetId)->lockForUpdate()->first();
        
        if ($nominal > $sisaTahunan) {
            return [
                'success' => false,
                'message' => "Plafon tahunan terlampaui. Sisa: " . number_format($sisaTahunan, 2, ',', '.'),
                'warning' => null,
            ];
        }

        // 2. Cek Pacing Bulanan (Soft Limit) - VERTIKAL
        $warningMessage = null;
        
        // Cari baris detail untuk bulan & tahun spesifik ini
        $pacingDetail = BudgetDetail::where('id_budget_master', $budgetId)
            ->where('bulan', $tanggal->month)
            ->where('tahun', $tanggal->year)
            ->first();

        if ($pacingDetail) {
            $plafonBulanan = (float) $pacingDetail->nominal_pacing;
            if ($plafonBulanan > 0 && $nominal > $plafonBulanan) {
                $warningMessage = "Peringatan: Melebihi pacing bulan ini (Plafon: " . number_format($plafonBulanan, 2, ',', '.') . ")";
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