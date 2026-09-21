<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AkunGl;
use App\Models\KasBank;
use App\Models\JurnalHeader;
use App\Models\JurnalDetail;
use Carbon\Carbon;

class MasterDataFinanceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan Akun GL untuk Bank tersedia
        $akunBankMasuk = AkunGl::firstOrCreate(
            ['kode_akun' => '1-1101'],
            ['nama_akun' => 'Bank Masuk', 'tipe_akun' => 'Aset']
        );

        $akunBankKeluar = AkunGl::firstOrCreate(
            ['kode_akun' => '1-1102'],
            ['nama_akun' => 'Bank Keluar', 'tipe_akun' => 'Aset']
        );

        $akunBankPayroll = AkunGl::firstOrCreate(
            ['kode_akun' => '1-1103'],
            ['nama_akun' => 'Bank Gaji', 'tipe_akun' => 'Aset']
        );

        $akunKasKecil = AkunGl::firstOrCreate(
            ['kode_akun' => '1-1100'],
            ['nama_akun' => 'Petty Cash', 'tipe_akun' => 'Aset']
        );

        // 2. Buat Akun Modal Awal (Equity Opening Balance)
        $akunModalAwal = AkunGl::firstOrCreate(
            ['kode_akun' => '3-0000'],
            ['nama_akun' => 'Modal Awal (Opening Balance Equity)', 'tipe_akun' => 'Modal']
        );

        // 3. Akun Penting Sistem (Pinjaman & Payroll)
        AkunGl::firstOrCreate(
            ['kode_akun' => '1-1200'],
            ['nama_akun' => 'Piutang Pinjaman Karyawan', 'tipe_akun' => 'Aset']
        );

        AkunGl::firstOrCreate(
            ['kode_akun' => '6-1000'],
            ['nama_akun' => 'Beban Gaji & Upah', 'tipe_akun' => 'Biaya']
        );

        // 4. Buat Data Kas/Bank dengan Saldo Awal + Jurnal
        
        $this->createBankWithJournal('Bank Masuk', '-', '-', $akunBankMasuk, 0, $akunModalAwal);
        $this->createBankWithJournal('Bank Keluar', '-', '-', $akunBankKeluar, 0, $akunModalAwal);
        $this->createBankWithJournal('Bank Gaji', '-', '-', $akunBankPayroll, 0, $akunModalAwal);
        $this->createBankWithJournal('Petty Cash', '-', '-', $akunKasKecil, 0, $akunModalAwal);

        $this->command->info('Master Data Finance (Kas/Bank + Jurnal Saldo Awal) berhasil dibuat.');
    }

    private function createBankWithJournal($namaBank, $rek, $atasNama, $akunGl, $saldoAwal, $akunModal)
    {
        // 1. Buat/Update Bank
        $bank = KasBank::firstOrCreate(
            ['nama_bank' => $namaBank],
            [
                'nomor_rekening' => $rek,
                'atas_nama' => $atasNama,
                'id_akun_gl' => $akunGl->id,
                'saldo_awal' => $saldoAwal,
                'is_active' => true
            ]
        );

        // 2. Cek apakah sudah punya jurnal saldo awal? Jika belum, buatkan.
        if (!$bank->id_jurnal_saldo_awal && $saldoAwal > 0) {
            
            // Buat Header Jurnal
            $jurnal = JurnalHeader::create([
                'nomor_jurnal' => 'JA-' . time() . '-' . rand(100, 999), // Format sementara
                'tgl_jurnal' => Carbon::now()->startOfYear(), // Asumsi saldo awal per 1 Jan
                'deskripsi_jurnal' => 'Saldo Awal ' . $namaBank,
                'tipe_transaksi' => 'Saldo Awal',
                'status' => 'Posted',
                'sumber_modul' => 'GL'
            ]);

            // Detail 1: Debit Bank (Aset Bertambah)
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id,
                'id_akun' => $akunGl->id,
                'debit' => $saldoAwal,
                'kredit' => 0,
                'keterangan_baris' => 'Saldo Awal'
            ]);

            // Detail 2: Kredit Modal (Ekuitas Bertambah)
            JurnalDetail::create([
                'id_jurnal' => $jurnal->id,
                'id_akun' => $akunModal->id,
                'debit' => 0,
                'kredit' => $saldoAwal,
                'keterangan_baris' => 'Penyeimbang Saldo Awal'
            ]);

            // Update Bank dengan ID Jurnal
            $bank->id_jurnal_saldo_awal = $jurnal->id;
            $bank->save();
        }
    }
}
