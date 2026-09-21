<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Departemen;

class DepartemenSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Finance',
            'Development',
            'General Affair',
            'IT',
            'Media & Publikasi',
            'Venue',
            'Direktur',
            'SS-SAWANGAN',
            'SS-KINGKONG',
            'SS-PULOMAS',
            'SS-BEKASI',
            'SS-CILEDUG',
            'BOARDING SCHOOL',
            'PROGRAM & EVENT',
            'SS-KUNINGAN',
            'SS-BINTARO',
            'Soccer School'
        ];

        foreach ($departments as $dept) {
            Departemen::firstOrCreate(['nama_departemen' => $dept]);
        }
        
        $this->command->info('17 Departemen berhasil diseder.');
    }
}
