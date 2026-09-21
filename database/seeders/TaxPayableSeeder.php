<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AkunGl;

class TaxPayableSeeder extends Seeder
{
    public function run()
    {
        // Hutang PPh 21 (Liability)
        $hutangPajak = AkunGl::firstOrCreate(
            ['nama_akun' => 'Hutang PPh 21'],
            [
                'kode_akun' => '2-2006',
                'tipe_akun' => 'Utang'
            ]
        );

        echo "ACC_TAX_PAYABLE_ID=" . $hutangPajak->id . "\n";
    }
}
