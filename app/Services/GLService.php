<?php

namespace App\Services;

use App\Models\JurnalHeader;
use App\Models\JurnalDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\DocumentNumberService;

class GLService
{
    /**
     * Create a new Journal Entry
     * 
     * @param string $date (Y-m-d)
     * @param string $description
     * @param array $details [ ['id_akun' => 1, 'debit' => 1000, 'kredit' => 0, 'keterangan' => '...'], ... ]
     * @param string $sourceModule (e.g., 'AP', 'AR', 'PAYMENT')
     * @param int|null $sourceRefId
     * @param string $transactionType (e.g., 'Payment', 'Invoice')
     * @return JurnalHeader
     * @throws \Exception
     */
    public static function createJournal($date, $description, $details, $sourceModule = 'GL', $sourceRefId = null, $transactionType = 'Manual')
    {
        // Validate Period Closing
        \App\Services\PeriodClosingService::checkClosed($date, 'Jurnal Umum / Transaksi Otomatis');

        // Validate Balance
        $totalDebit = collect($details)->sum('debit');
        $totalCredit = collect($details)->sum('kredit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \Exception("Journal Unbalanced: Debit $totalDebit != Credit $totalCredit");
        }

        return DB::transaction(function () use ($date, $description, $details, $sourceModule, $sourceRefId, $transactionType) {
            $header = JurnalHeader::create([
                'nomor_jurnal' => DocumentNumberService::jurnal(),
                'tgl_jurnal' => $date,
                'deskripsi_jurnal' => $description,
                'tipe_transaksi' => $transactionType,
                'status' => 'Posted',
                'created_by' => Auth::id() ?? 1, // Fallback to system user if no auth
                'sumber_modul' => $sourceModule,
                'id_referensi_sumber' => $sourceRefId
            ]);

            foreach ($details as $detail) {
                if ($detail['debit'] > 0 || $detail['kredit'] > 0) {
                    JurnalDetail::create([
                        'id_jurnal' => $header->id,
                        'id_akun' => $detail['id_akun'],
                        'debit' => $detail['debit'],
                        'kredit' => $detail['kredit'],
                        'keterangan_baris' => $detail['keterangan'] ?? null
                    ]);
                }
            }

            return $header;
        });
    }
}
