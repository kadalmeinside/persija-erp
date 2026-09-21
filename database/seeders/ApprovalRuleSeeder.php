<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Departemen;
use App\Models\Karyawan;

class ApprovalRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan data departemen dan karyawan ada
        $deptIT = Departemen::where('nama_departemen', 'LIKE', '%IT%')->first() ?? Departemen::find(1);
        
        // Ambil dummy karyawan sebagai approver (Misal Boss A dan Boss B)
        // Pastikan Anda sudah menjalankan KaryawanSeeder sebelumnya
        $boss1 = Karyawan::find(1); 
        $boss2 = Karyawan::find(2);

        if ($deptIT && $boss1 && $boss2) {
            // Skenario: Dept IT butuh 2 orang approval
            DB::table('tbl_approval_rules')->insert([
                [
                    'id_departemen' => $deptIT->id,
                    'level_order' => 1,
                    'id_karyawan_approver' => $boss1->id, // Layer 1: Boss 1
                    'label_aksi' => 'Diketahui Manajer',
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'id_departemen' => $deptIT->id,
                    'level_order' => 2,
                    'id_karyawan_approver' => $boss2->id, // Layer 2: Boss 2 (Finance/Direktur)
                    'label_aksi' => 'Disetujui Finance',
                    'tipe' => 'Pengajuan',
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'id_departemen' => $deptIT->id,
                    'level_order' => 1,
                    'id_karyawan_approver' => $boss2->id, // Layer 1 Pinjaman: Boss 2 (HR/Finance)
                    'label_aksi' => 'Disetujui HR/Finance',
                    'tipe' => 'Pinjaman',
                    'created_at' => now(), 'updated_at' => now(),
                ]
            ]);
        }
    }
}