<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * DocumentNumberService
 *
 * Menghasilkan nomor dokumen unik yang aman secara atomik menggunakan DB lock.
 * Format: {PREFIX}/{YYYY}{MM}/{NNNN}
 * Contoh:  PJ/202604/0001,  V/202604/0001,  KAY/202604/0015
 */
class DocumentNumberService
{
    /**
     * Generate nomor dokumen berikutnya secara atomik.
     *
     * @param  string  $prefix       Prefix dokumen, misal 'PJ', 'V', 'PAY'
     * @param  string  $table        Nama tabel untuk query sequence
     * @param  string  $column       Nama kolom yang menyimpan nomor
     * @param  string  $pattern      Pattern LIKE untuk filter, misal 'PJ/%'
     * @param  int     $pad          Panjang angka urut (default 4 → 0001)
     * @param  string|null $yearMonth Override YYYYMM (opsional, untuk testing)
     * @return string
     */
    public static function generate(
        string $prefix,
        string $table,
        string $column,
        string $pattern = null,
        int $pad = 4,
        ?string $yearMonth = null
    ): string {
        $ym = $yearMonth ?? now()->format('Ym');
        $likePattern = $pattern ?? "{$prefix}/{$ym}/%";

        return DB::transaction(function () use ($prefix, $table, $column, $likePattern, $pad, $ym) {
            // Lock row untuk hindari race condition
            $last = DB::table($table)
                ->where($column, 'like', $likePattern)
                ->lockForUpdate()
                ->orderByDesc($column)
                ->value($column);

            $next = 1;

            if ($last) {
                // Ambil angka urut dari akhir nomor: "PJ/202604/0007" → 7
                $parts = explode('/', $last);
                $lastSeq = (int) end($parts);
                $next = $lastSeq + 1;
            }

            return "{$prefix}/{$ym}/" . str_pad($next, $pad, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Shorthand untuk Pengajuan (PJ).
     */
    public static function pengajuan(?string $yearMonth = null): string
    {
        return self::generate('PJ', 'tbl_pengajuan_header', 'nomor_pengajuan', yearMonth: $yearMonth);
    }

    /**
     * Shorthand untuk Vendor (V).
     */
    public static function vendor(?string $yearMonth = null): string
    {
        return self::generate('V', 'tbl_vendor', 'kode_vendor', yearMonth: $yearMonth);
    }

    /**
     * Shorthand untuk Payroll (PAY).
     */
    public static function payroll(?string $yearMonth = null): string
    {
        return self::generate('PAY', 'tbl_pengajuan_header', 'nomor_pengajuan', yearMonth: $yearMonth);
    }

    /**
     * Shorthand untuk Pengajuan Pinjaman (PJ-LOAN).
     */
    public static function pinjaman(?string $yearMonth = null): string
    {
        return self::generate('PJ-LOAN', 'tbl_pengajuan_header', 'nomor_pengajuan', yearMonth: $yearMonth);
    }

    /**
     * Shorthand untuk Customer (CUST).
     */
    public static function customer(?string $yearMonth = null): string
    {
        return self::generate('CUST', 'tbl_pelanggan', 'kode_pelanggan', yearMonth: $yearMonth);
    }

    /**
     * Shorthand untuk Jurnal Adjustment (JA).
     */
    public static function jurnalAdjustment(?string $yearMonth = null): string
    {
        return self::generate('JA', 'tbl_jurnal_header', 'nomor_jurnal', yearMonth: $yearMonth);
    }

    /**
     * Shorthand untuk Jurnal Umum/Otomatis (JR).
     * Digunakan oleh GLService::createJournal() untuk semua posting GL otomatis.
     * Menggunakan lockForUpdate() sehingga bebas race condition meskipun concurrent.
     */
    public static function jurnal(?string $yearMonth = null): string
    {
        return self::generate('JR', 'tbl_jurnal_header', 'nomor_jurnal', yearMonth: $yearMonth);
    }

    /**
     * Shorthand untuk Invoice Penjualan (INV).
     * Menggantikan pola race-condition lama: INV-{Ymd}-{seq}
     */
    public static function invoice(?string $yearMonth = null): string
    {
        return self::generate('INV', 'tbl_invoice_header', 'nomor_invoice', yearMonth: $yearMonth);
    }

    /**
     * Shorthand untuk Internal Transfer (IT).
     */
    public static function internalTransfer(?string $yearMonth = null): string
    {
        return self::generate('IT', 'tbl_internal_transfer', 'nomor_transfer', yearMonth: $yearMonth);
    }
}
