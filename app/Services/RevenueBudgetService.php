<?php

namespace App\Services;

use App\Models\InvoiceHeader;
use App\Models\BudgetMaster;
use App\Models\PosAnggaran;
use App\Models\PeriodeAnggaran;
use Illuminate\Support\Facades\Log;

class RevenueBudgetService
{
    /**
     * Update Realisasi Anggaran Pendapatan berdasarkan Invoice.
     * Dipanggil saat Invoice dibuat (Status != Draft) atau diupdate.
     */
    public function updateRealization(InvoiceHeader $invoice)
    {
        // Hanya proses jika status bukan Draft
        if ($invoice->status === 'Draft') {
            return;
        }

        $periodeId = $this->getPeriodeId($invoice->tgl_invoice);
        if (!$periodeId) {
            Log::warning("RevenueBudgetService: Periode anggaran tidak ditemukan untuk tanggal " . $invoice->tgl_invoice);
            return;
        }

        foreach ($invoice->detail as $item) {
            if (!$item->id_akun_pendapatan) continue;

            // 1. Cari Pos Anggaran (Program) yang cocok
            // Logic: Cari Pos Anggaran di Departemen Invoice yang menggunakan Akun ini
            // Note: Pos Anggaran ada di tabel tbl_pos_anggaran. 
            // Relasi: PosAnggaran -> ProgramKerja -> Departemen
            
            $posAnggaran = PosAnggaran::where('id_akun_gl', $item->id_akun_pendapatan)
                ->whereHas('programKerja', function($q) use ($invoice) {
                    $q->where('id_departemen', $invoice->id_departemen);
                })
                ->first();

            if ($posAnggaran) {
                // 2. Cari Budget Master untuk Pos ini di Periode ini
                $budget = BudgetMaster::where('id_periode_anggaran', $periodeId)
                    ->where('id_pos_anggaran', $posAnggaran->id)
                    ->first();

                if ($budget) {
                    // 3. Update Realisasi
                    // Kita increment. Hati-hati duplikasi jika dipanggil berulang.
                    // Idealnya kita hitung ulang atau pastikan ini hanya dipanggil sekali.
                    // Untuk aman: Kita asumsikan ini dipanggil saat CREATION atau STATUS CHANGE.
                    // Jika Edit, kita harus handle reversal dulu (TODO).
                    
                    $budget->increment('anggaran_realisasi_ytd', $item->total_harga);
                    
                    Log::info("RevenueBudgetService: Updated Budget ID {$budget->id} (+{$item->total_harga}) from Invoice {$invoice->nomor_invoice}");
                } else {
                    Log::info("RevenueBudgetService: Budget Master not found for Pos ID {$posAnggaran->id} in Periode {$periodeId}");
                }
            } else {
                Log::info("RevenueBudgetService: Pos Anggaran not found for Account {$item->id_akun_pendapatan} in Dept {$invoice->id_departemen}");
            }
        }
    }

    private function getPeriodeId($date)
    {
        $periode = PeriodeAnggaran::whereDate('tanggal_mulai', '<=', $date)
                    ->whereDate('tanggal_selesai', '>=', $date)
                    ->first();
        return $periode ? $periode->id : null;
    }
}
