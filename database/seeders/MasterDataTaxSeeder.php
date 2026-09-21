<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\AkunGl;
use App\Models\TaxType;

class MasterDataTaxSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Akun GL untuk Pajak
        
        // Hutang PPN (Liability)
        $akunHutangPPN = AkunGl::firstOrCreate(
            ['kode_akun' => '2-1100'],
            ['nama_akun' => 'Hutang PPN (VAT Payable)', 'tipe_akun' => 'Utang']
        );

        // Piutang PPh 23 (Asset - Prepaid Tax)
        $akunPiutangPPh = AkunGl::firstOrCreate(
            ['kode_akun' => '1-1400'],
            ['nama_akun' => 'Piutang PPh 23 (Prepaid Tax)', 'tipe_akun' => 'Aset']
        );

        // 2. Buat Master Data Pajak

        // PPN 11%
        TaxType::firstOrCreate(
            ['kode_pajak' => 'PPN11'],
            [
                'nama_pajak' => 'PPN 11%',
                'rate' => 11.00,
                'tipe' => 'PPN',
                'id_akun_gl' => $akunHutangPPN->id,
                'is_active' => true
            ]
        );

        // PPN 12% (Future Use)
        TaxType::firstOrCreate(
            ['kode_pajak' => 'PPN12'],
            [
                'nama_pajak' => 'PPN 12%',
                'rate' => 12.00,
                'tipe' => 'PPN',
                'id_akun_gl' => $akunHutangPPN->id,
                'is_active' => false // Inactive by default
            ]
        );

        // PPh 23 (Jasa)
        TaxType::firstOrCreate(
            ['kode_pajak' => 'PPH23'],
            [
                'nama_pajak' => 'PPh 23 (Jasa)',
                'rate' => 2.00,
                'tipe' => 'PPh',
                'id_akun_gl' => $akunPiutangPPh->id,
                'is_active' => true
            ]
        );

        $this->command->info('Master Data Pajak (GL Accounts & Tax Types) berhasil dibuat.');
    }
}
