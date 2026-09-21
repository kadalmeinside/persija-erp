<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AkunGl;

class PayrollAccountSeeder extends Seeder
{
    public function run()
    {
        // 1. Beban Gaji (Expense)
        $bebanGaji = AkunGl::firstOrCreate(
            ['nama_akun' => 'Beban Gaji'],
            [
                'kode_akun' => '6-004',
                'tipe_akun' => 'Biaya'
            ]
        );

        // 2. Hutang Gaji (Liability)
        $hutangGaji = AkunGl::firstOrCreate(
            ['nama_akun' => 'Hutang Gaji'],
            [
                'kode_akun' => '2-2005',
                'tipe_akun' => 'Utang'
            ]
        );

        echo "ACC_SALARY_EXPENSE_ID=" . $bebanGaji->id . "\n";
        echo "ACC_SALARIES_PAYABLE_ID=" . $hutangGaji->id . "\n";
    }
}
