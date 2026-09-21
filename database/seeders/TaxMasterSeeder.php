<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaxMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Department
        $dept = \App\Models\Departemen::firstOrCreate(
            ['nama_departemen' => 'Finance & Accounting']
        );

        // 2. Create Program
        $program = \App\Models\ProgramKerja::firstOrCreate(
            ['nama_program' => 'Pengelolaan Keuangan & Perpajakan'],
            ['id_departemen' => $dept->id]
        );

        // 3. Create Accounts (Liabilities)
        $accounts = [
            ['kode' => '2-2001', 'nama' => 'Hutang PPN (VAT Payable)', 'tipe' => 'Utang'],
            ['kode' => '2-2002', 'nama' => 'Hutang PPh 21', 'tipe' => 'Utang'],
            ['kode' => '2-2003', 'nama' => 'Hutang PPh 23', 'tipe' => 'Utang'],
            ['kode' => '2-2004', 'nama' => 'Hutang PPh 4 Ayat 2', 'tipe' => 'Utang'],
            ['kode' => '1-1005', 'nama' => 'Piutang PPN (VAT Receivable)', 'tipe' => 'Aset'], // For Input Tax
        ];

        foreach ($accounts as $acc) {
            $akun = \App\Models\AkunGl::firstOrCreate(
                ['kode_akun' => $acc['kode']],
                ['nama_akun' => $acc['nama'], 'tipe_akun' => $acc['tipe']]
            );

            // 4. Map to Program (Pos Anggaran) - Optional but good for validation
            // Check if mapping exists
            $exists = \Illuminate\Support\Facades\DB::table('tbl_pos_anggaran')
                ->where('id_program_kerja', $program->id)
                ->where('id_akun_gl', $akun->id)
                ->exists();
            
            if (!$exists) {
                \Illuminate\Support\Facades\DB::table('tbl_pos_anggaran')->insert([
                    'id_program_kerja' => $program->id,
                    'id_akun_gl' => $akun->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        // 5. Create Tax Types
        $taxTypes = [
            // PURCHASE (EXPENSE)
            [
                'kode' => 'PPN-IN',
                'nama' => 'PPN Masukan 11%',
                'rate' => 11.00,
                'tipe' => 'PPN',
                'akun_kode' => '1-1005', // Piutang PPN (Aset)
                'trans_type' => 'purchase',
                'program_id' => $program->id
            ],
            [
                'kode' => 'PPH23-JASA',
                'nama' => 'PPh 23 Jasa (2%)',
                'rate' => 2.00,
                'tipe' => 'PPh',
                'akun_kode' => '2-2003', // Hutang PPh 23
                'trans_type' => 'purchase',
                'program_id' => $program->id
            ],
            [
                'kode' => 'PPH21-TA',
                'nama' => 'PPh 21 Tenaga Ahli (2.5%)',
                'rate' => 2.50,
                'tipe' => 'PPh',
                'akun_kode' => '2-2002', // Hutang PPh 21
                'trans_type' => 'purchase',
                'program_id' => $program->id
            ],
            [
                'kode' => 'PPH42-SEWA',
                'nama' => 'PPh 4(2) Sewa (10%)',
                'rate' => 10.00,
                'tipe' => 'PPh',
                'akun_kode' => '2-2004', // Hutang PPh 4(2)
                'trans_type' => 'purchase',
                'program_id' => $program->id
            ],

            // SALES (INCOME)
            [
                'kode' => 'PPN-OUT',
                'nama' => 'PPN Keluaran 11%',
                'rate' => 11.00,
                'tipe' => 'PPN',
                'akun_kode' => '2-2001', // Hutang PPN (Kewajiban)
                'trans_type' => 'sales',
                'program_id' => $program->id
            ],
        ];

        foreach ($taxTypes as $tax) {
            $akun = \App\Models\AkunGl::where('kode_akun', $tax['akun_kode'])->first();
            if ($akun) {
                \App\Models\TaxType::updateOrCreate(
                    ['kode_pajak' => $tax['kode']],
                    [
                        'nama_pajak' => $tax['nama'],
                        'rate' => $tax['rate'],
                        'tipe' => $tax['tipe'],
                        'id_akun_gl' => $akun->id,
                        'transaction_type' => $tax['trans_type'],
                        'id_program' => $tax['program_id'],
                        'is_active' => true
                    ]
                );
            }
        }
    }
}
