<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Departemen;
use App\Models\ProgramKerja;
use App\Models\AkunGl;
use App\Models\BudgetMaster;

class MasterDataBudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = 'seeders/Dtail-anggaran.csv'; 
        $tahunAnggaran = 2025;

        if (!Storage::disk('local')->exists($filePath)) {
            $this->command->error("File anggaran tidak ditemukan di: storage/app/{$filePath}");
            return;
        }

        $this->command->info("Memulai parsing file anggaran: {$filePath}...");

        DB::transaction(function () use ($filePath, $tahunAnggaran) {
            
            $currentDepartemenId = null;
            $currentProgramId = null;
            $currentTipeAkun = null; 
            $skipRows = 4;

            $file = Storage::disk('local')->readStream($filePath);

            while (($row = fgetcsv($file)) !== false) {
                
                if ($skipRows > 0) {
                    $skipRows--;
                    continue;
                }

                if (empty(implode('', $row))) {
                    continue;
                }

                $kodeAkun = trim($row[0] ?? '');
                $uraian = trim($row[1] ?? '');
                $anggaranTotal = $this->cleanNumeric($row[2] ?? 0);

                if (strtoupper($kodeAkun) == 'PENDAPATAN') {
                    $currentTipeAkun = 'Pendapatan';
                    $this->command->info("Mengganti Tipe Akun ke: PENDAPATAN");
                    continue;
                }
                if (strtoupper($kodeAkun) == 'BIAYA' || preg_match('/^5-10\. INVENTARIS/', $kodeAkun)) {
                    $currentTipeAkun = 'Biaya';
                    $this->command->info("Mengganti Tipe Akun ke: BIAYA");
                    continue;
                }

                if ((preg_match('/^[45]-\d{2}\./', $kodeAkun) || !empty($uraian)) && $anggaranTotal == 0) {
                    
                    if (empty($kodeAkun) && !empty($uraian)) {
                        $program = ProgramKerja::firstOrCreate(['nama_program' => $uraian]);
                        $currentProgramId = $program->id;
                        $this->command->getOutput()->writeln("<comment>  -> Mengganti Program ke: {$uraian}</comment>");
                    }
                    else {
                        $namaDept = !empty($kodeAkun) ? $kodeAkun : $uraian;
                        $dept = Departemen::firstOrCreate(['nama_departemen' => $namaDept]);
                        $currentDepartemenId = $dept->id;
                        $currentProgramId = null; 
                        $this->command->info("Mengganti Departemen ke: {$namaDept}");
                    }
                    continue;
                }

                if (preg_match('/^[45]-\d{4}-\d{2}$/', $kodeAkun) && $currentDepartemenId && $currentProgramId) {

                    // Tentukan Tipe Akun
                    $tipeAkun = 'Biaya'; 
                    if (str_starts_with($kodeAkun, '4-')) {
                        $tipeAkun = 'Pendapatan';
                    } else if (str_starts_with($kodeAkun, '5-1001')) {
                        $tipeAkun = 'Biaya Modal'; 
                    }

                    // Buat Akun GL jika belum ada
                    $akun = AkunGl::firstOrCreate(
                        ['kode_akun' => $kodeAkun],
                        ['nama_akun' => $uraian, 'tipe_akun' => $tipeAkun]
                    );

                    // Buat entri Budget Master
                    BudgetMaster::create([
                        'tahun' => $tahunAnggaran,
                        'id_departemen' => $currentDepartemenId,
                        'id_akun' => $akun->id,
                        'id_program' => $currentProgramId,
                        'anggaran_total_tahun' => $anggaranTotal,
                        'anggaran_terikat_ytd' => 0,
                        'anggaran_realisasi_ytd' => 0,
                        'pacing_jan' => $this->cleanNumeric($row[3] ?? 0),
                        'pacing_feb' => $this->cleanNumeric($row[4] ?? 0),
                        'pacing_mar' => $this->cleanNumeric($row[5] ?? 0),
                        'pacing_apr' => $this->cleanNumeric($row[6] ?? 0),
                        'pacing_mei' => $this->cleanNumeric($row[7] ?? 0),
                        'pacing_jun' => $this->cleanNumeric($row[8] ?? 0),
                        'pacing_jul' => $this->cleanNumeric($row[9] ?? 0),
                        'pacing_agu' => $this->cleanNumeric($row[10] ?? 0),
                        'pacing_sep' => $this->cleanNumeric($row[11] ?? 0),
                        'pacing_okt' => $this->cleanNumeric($row[12] ?? 0),
                        'pacing_nov' => $this->cleanNumeric($row[13] ?? 0),
                        'pacing_des' => $this->cleanNumeric($row[14] ?? 0),
                    ]);
                    $this->command->getOutput()->writeln("<info>    -> Seeded Budget: {$uraian}</info>");
                }

            } // end while

            fclose($file);

        }); // end transaction

        $this->command->info("Proses Seeding Master Data dan Anggaran selesai.");
    }

    /**
     * Membersihkan string numerik dari file CSV (menghapus ',' atau '.').
     */
    private function cleanNumeric($value): float
    {
        if (is_null($value) || $value === '') {
            return 0;
        }
        $value = str_replace('.', '', $value); 
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }
}