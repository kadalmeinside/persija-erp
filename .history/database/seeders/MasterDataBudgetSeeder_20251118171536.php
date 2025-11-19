<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Departemen;
use App\Models\ProgramKerja;
use App\Models\AkunGl;
use App\Models\BudgetMaster;

class MasterDataBudgetSeeder extends Seeder
{
    public function run(): void
    {
        $fileName = "D'tail-anggaran-dummy-v2.csv"; 
        $absolutePath = storage_path("app/seeders/{$fileName}");
        $tahunAnggaran = 2025; 

        if (!file_exists($absolutePath)) {
            $this->command->error("ERROR: File tidak ditemukan di {$absolutePath}");
            return;
        }

        $this->command->info("Memulai parsing file: {$fileName}");

        DB::transaction(function () use ($absolutePath, $tahunAnggaran) {
            
            $currentDepartemenId = null;
            $currentProgramId = null;
            $lastKodeAkunPrefix = '5-0000'; 

            if (($handle = fopen($absolutePath, "r")) !== false) {
                
                $rowNumber = 0;
                while (($row = fgetcsv($handle, 0, ';')) !== false) {
                    $rowNumber++;
                    
                    if (empty(implode('', $row)) || count($row) < 3) continue;

                    $kodeAkun = trim($row[0] ?? '');
                    $uraian = trim($row[1] ?? '');
                    $anggaranTotal = $this->cleanNumeric($row[2] ?? 0);

                    if (in_array(strtoupper($kodeAkun), ['BIAYA', 'PENDAPATAN', 'KODE AKUN'])) continue;
                    if (in_array(strtoupper($uraian), ['BIAYA', 'PENDAPATAN'])) continue;

                    // --- 1. DETEKSI DEPARTEMEN ---
                    $isDepartemen = false;
                    if (preg_match('/^[45]-\d{2}\./', $kodeAkun)) {
                        $isDepartemen = true;
                    } 
                    
                    if ($isDepartemen) {
                        $namaDept = !empty($kodeAkun) ? $kodeAkun : $uraian;
                        $namaDeptClean = preg_replace('/^[45]-\d{2}\.\s*/', '', $namaDept);
                        
                        $dept = Departemen::firstOrCreate(['nama_departemen' => $namaDeptClean]);
                        $currentDepartemenId = $dept->id;
                        $currentProgramId = null; // Reset program saat ganti departemen
                        
                        preg_match('/^([45]-\d{2})/', $kodeAkun, $matches);
                        if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];

                        $this->command->info("  [DEPT] {$namaDeptClean}");
                        continue;
                    }

                    // --- 2. DETEKSI PROGRAM KERJA ---
                    // Logika: Kode Akun KOSONG, Uraian ADA, Anggaran KOSONG/0
                    // Ini menangkap baris seperti "Gaji & Tunjangan", "Operasional Kantor"
                    if (empty($kodeAkun) && !empty($uraian) && $anggaranTotal == 0) {
                        $program = ProgramKerja::firstOrCreate(['nama_program' => $uraian]);
                        $currentProgramId = $program->id;
                        $this->command->info("    [PROG] {$uraian}");
                        continue;
                    }

                    // --- 3. DETEKSI DATA ANGGARAN (AKUN) ---
                    $isAkun = ($anggaranTotal > 0) || preg_match('/^[45]-\d{4}-\d{2}/', $kodeAkun);

                    if ($isAkun) {
                        // Validasi Ketat: Harus ada Dept dan Program
                        if (!$currentDepartemenId) {
                            $this->command->warn("    ! SKIP Row {$rowNumber}: '{$uraian}' - Tidak ada Departemen aktif.");
                            continue;
                        }

                        if (!$currentProgramId) {
                            $this->command->warn("    ! SKIP Row {$rowNumber}: '{$uraian}' - Tidak ada Program aktif.");
                            continue;
                        }

                        // Proses Akun & Budget
                        $finalKodeAkun = $kodeAkun;
                        if (empty($finalKodeAkun)) {
                            $randomSuffix = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
                            $finalKodeAkun = $lastKodeAkunPrefix . '-99-' . $randomSuffix;
                        } else {
                             preg_match('/^([45]-\d{4})/', $finalKodeAkun, $matches);
                             if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];
                        }

                        $tipeAkun = str_starts_with($finalKodeAkun, '4') ? 'Pendapatan' : 'Biaya';
                        $akun = AkunGl::firstOrCreate(
                            ['kode_akun' => $finalKodeAkun],
                            ['nama_akun' => $uraian, 'tipe_akun' => $tipeAkun]
                        );

                        $exists = BudgetMaster::where('tahun', $tahunAnggaran)
                            ->where('id_departemen', $currentDepartemenId)
                            ->where('id_akun', $akun->id)
                            ->where('id_program', $currentProgramId)
                            ->exists();

                        if (!$exists) {
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
                            $this->command->getOutput()->write('.'); 
                        }
                    }

                } // end while
                
                fclose($handle);
                $this->command->info("\nSelesai parsing.");
            }
        });
    }

    private function cleanNumeric($value): float
    {
        if (is_null($value) || $value === '') return 0;
        $value = trim($value); 
        $value = str_replace('.', '', $value); 
        $value = str_replace(',', '.', $value); 
        $value = preg_replace('/[^0-9\.-]/', '', $value); 
        return (float) $value;
    }
}