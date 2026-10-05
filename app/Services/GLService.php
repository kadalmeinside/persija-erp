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
        $totalDebit = collect($details)->sum(fn (array $detail) => (float) ($detail['debit'] ?? 0));
        $totalCredit = collect($details)->sum(fn (array $detail) => (float) ($detail['kredit'] ?? 0));

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
                'id_referensi_sumber' => $sourceRefId,
                'source_type' => $sourceRefId ? self::sourceType($sourceModule) : null,
                'source_event' => $transactionType,
                'posting_batch_id' => (string) \Illuminate\Support\Str::uuid(),
            ]);

            foreach ($details as $detail) {
                $debit = (float) ($detail['debit'] ?? 0);
                $credit = (float) ($detail['kredit'] ?? 0);
                if ($debit < 0 || $credit < 0 || ($debit > 0 && $credit > 0)) {
                    throw new \InvalidArgumentException('Each journal line must have non-negative values and only one side populated.');
                }

                if ($debit > 0 || $credit > 0) {
                    JurnalDetail::create([
                        'id_jurnal' => $header->id,
                        'id_akun' => $detail['id_akun'],
                        'debit' => $debit,
                        'kredit' => $credit,
                        'keterangan_baris' => $detail['keterangan'] ?? null
                    ]);
                }
            }

            return $header;
        });
    }

    private static function sourceType(string $sourceModule): string
    {
        return match ($sourceModule) {
            'AP' => 'pengajuan',
            'AR' => 'invoice',
            'FA' => 'aset',
            'HR' => 'payroll',
            'INV' => 'inventory',
            default => 'journal',
        };
    }
}
