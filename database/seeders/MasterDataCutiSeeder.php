<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use Carbon\Carbon;

class MasterDataCutiSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Define Leave Types & Quotas
        // 1. Define Leave Types & Quotas
        $cutiTypes = [
            ['nama_cuti' => 'Cuti Tahunan', 'kuota_default' => 12, 'bisa_mundur' => false],
            ['nama_cuti' => 'Cuti Sakit', 'kuota_default' => 14, 'bisa_mundur' => true],
            ['nama_cuti' => 'Cuti Menikah', 'kuota_default' => 3, 'bisa_mundur' => false],
            ['nama_cuti' => 'Cuti Melahirkan', 'kuota_default' => 90, 'bisa_mundur' => false, 'khusus_perempuan' => true],
            ['nama_cuti' => 'Cuti Khusus (Kedukaan/Baptis/Khitan)', 'kuota_default' => 2, 'bisa_mundur' => true],
            ['nama_cuti' => 'Unpaid Leave', 'kuota_default' => 0, 'bisa_mundur' => false], // 0 means unlimited/special logic
        ];

        foreach ($cutiTypes as $type) {
            JenisCuti::updateOrCreate(
                ['nama_cuti' => $type['nama_cuti']],
                [
                    'kuota_default' => $type['kuota_default'],
                    'bisa_mundur' => $type['bisa_mundur']
                ]
            );
        }

        $this->command->info('Master Data Jenis Cuti created.');

        // 2. Assign Initial Balances for All Existing Employees (Current Year)
        $karyawans = Karyawan::all();
        $currentYear = Carbon::now()->year;
        $allTypes = JenisCuti::all();

        foreach ($karyawans as $karyawan) {
            foreach ($allTypes as $jenisCuti) {
                
                // Skip Unpaid Leave balance creation if we treat it as no-balance
                // But for consistency, we can create it with 0 balance or just skip.
                // Let's create it with 0 balance, logic will allow negative or ignore balance for Unpaid.
                
                // Logic for Maternity Leave (Khusus Perempuan)
                $quota = $jenisCuti->kuota_default;
                
                if ($jenisCuti->khusus_perempuan && $karyawan->jenis_kelamin !== 'P') {
                    $quota = 0; // Set quota to 0 for males
                }

                SaldoCuti::firstOrCreate(
                    [
                        'id_karyawan' => $karyawan->id,
                        'id_jenis_cuti' => $jenisCuti->id,
                        'tahun_periode' => $currentYear
                    ],
                    [
                        'saldo_awal' => 0, // Reset ke 0 sesuai request
                        'saldo_terpakai' => 0,
                        'saldo_akhir' => 0 
                    ]
                );
            }
        }

        $this->command->info('Initial Leave Balances assigned to ' . $karyawans->count() . ' employees for year ' . $currentYear);
    }
}
