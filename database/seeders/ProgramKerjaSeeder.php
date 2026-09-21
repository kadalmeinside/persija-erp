<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProgramKerja;
use App\Models\Departemen;

class ProgramKerjaSeeder extends Seeder
{
    public function run()
    {
        // Ensure a department exists
        $dept = Departemen::firstOrCreate(['nama_departemen' => 'Finance & Accounting']);

        $program = ProgramKerja::firstOrCreate(
            ['nama_program' => 'Operasional Rutin'],
            ['id_departemen' => $dept->id]
        );

        echo "DEFAULT_PROGRAM_ID=" . $program->id . "\n";
    }
}
