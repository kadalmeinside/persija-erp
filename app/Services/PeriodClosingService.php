<?php

namespace App\Services;

use App\Models\PeriodeClosing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PeriodClosingService
{
    /**
     * Check if a specific date is closed
     *
     * @param string $dateString (Y-m-d)
     * @return void
     * @throws \Exception
     */
    public static function checkClosed($dateString, $moduleName = 'Translasi')
    {
        if (PeriodeClosing::isDateClosed($dateString)) {
            $month = Carbon::parse($dateString)->locale('id')->translatedFormat('F Y');
            throw new \Exception("Periode akuntansi ($month) sudah ditutup. Tidak dapat memproses dokumen/jurnal $moduleName pada tanggal tersebut.");
        }
    }

    /**
     * Close a period
     */
    public static function closePeriod($bulan, $tahun, $catatan = null)
    {
        $periode = PeriodeClosing::firstOrCreate(
            ['bulan' => $bulan, 'tahun' => $tahun]
        );

        if ($periode->status === 'Closed') {
            throw new \Exception('Periode ini sudah ditutup sebelumnya.');
        }

        $periode->update([
            'status' => 'Closed',
            'closed_by' => Auth::id() ?? 1,
            'closed_at' => now(),
            'catatan' => $catatan
        ]);

        return $periode;
    }

    /**
     * Reopen a closed period
     */
    public static function reopenPeriod($bulan, $tahun, $catatan)
    {
        $periode = PeriodeClosing::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        if (!$periode || $periode->status === 'Open') {
            throw new \Exception('Periode ini belum ditutup atau tidak ditemukan.');
        }

        $periode->update([
            'status' => 'Open',
            'reopened_by' => Auth::id() ?? 1,
            'reopened_at' => now(),
            // Append note to keep history of closing/opening
            'catatan' => $periode->catatan . "\n[Reopened]: " . $catatan
        ]);

        return $periode;
    }
}
