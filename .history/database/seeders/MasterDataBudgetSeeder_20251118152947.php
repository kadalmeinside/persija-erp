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
        // Pastikan nama file sesuai (Case Sensitive di Linux/Server)
        $fileName = "D'tail-anggaran.csv"; 
        $absolutePath = storage_path("app/seeders/{$fileName}");
        $tahunAnggaran = 2025; 

        $this->command->info("Mencari file di: {$absolutePath}");

        if (!file_exists($absolutePath)) {
            $this->command->error("ERROR: File tidak ditemukan!");
            return;
        }

        $this->command->info("File ditemukan! Memulai parsing...");

        DB::transaction(function () use ($absolutePath, $tahunAnggaran) {
            
            $currentDepartemenId = null;
            $currentProgramId = null;
            $lastKodeAkunPrefix = '5-0000'; 

            if (($handle = fopen($absolutePath, "r")) !== false) {
                
                $rowNumber = 0;
                while (($row = fgetcsv($handle, 0, ';')) !== false) {
                    $rowNumber++;
                    
                    // Debugging: Cetak 10 baris pertama untuk memastikan pembacaan benar
                    if ($rowNumber <= 10) {
                        // $this->command->info("Row {$rowNumber}: " . implode(' | ', $row));
                    }

                    // Lewati baris kosong atau header yang tidak relevan
                    if (empty(implode('', $row)) || count($row) < 3) continue;

                    $kodeAkun = trim($row[0] ?? '');
                    $uraian = trim($row[1] ?? '');
                    // Bersihkan angka anggaran (Hapus titik ribuan)
                    $anggaranTotal = $this->cleanNumeric($row[2] ?? 0);

                    // 1. Deteksi Header Utama (BIAYA / PENDAPATAN) - Skip saja
                    if (in_array(strtoupper($kodeAkun), ['BIAYA', 'PENDAPATAN', 'KODE AKUN'])) continue;
                    if (in_array(strtoupper($uraian), ['BIAYA', 'PENDAPATAN'])) continue;

                    // 2. Deteksi DEPARTEMEN (Ciri: Kode Akun format '5-XX.' atau Uraian ada tapi Anggaran 0)
                    // Contoh: "5-01. BOARDING SCHOOL"
                    if (preg_match('/^[45]-\d{2}\./', $kodeAkun) || ($anggaranTotal == 0 && !empty($uraian) && empty($kodeAkun) == false)) {
                        
                        // Cek apakah ini Departemen atau Program?
                        // Asumsi: Departemen biasanya ada Kode Akun-nya (5-01.) atau dia adalah header besar
                        if (str_contains($kodeAkun, '.') || (empty($kodeAkun) && $currentDepartemenId == null)) {
                            $namaDept = !empty($kodeAkun) ? $kodeAkun : $uraian;
                            // Bersihkan nama dept (hapus '5-01. ')
                            $namaDeptClean = preg_replace('/^[45]-\d{2}\.\s*/', '', $namaDept);
                            
                            $dept = Departemen::firstOrCreate(['nama_departemen' => $namaDeptClean]);
                            $currentDepartemenId = $dept->id;
                            $currentProgramId = null; // Reset program
                            
                            // Ambil prefix untuk kode akun dummy (misal 5-01)
                            preg_match('/^([45]-\d{2})/', $kodeAkun, $matches);
                            if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];

                            $this->command->info("[DEPT] Set Departemen: {$namaDeptClean}");
                        } 
                        // Jika Anggaran 0 tapi bukan Dept, mungkin ini Program?
                        else if ($currentDepartemenId) {
                            $program = ProgramKerja::firstOrCreate(['nama_program' => $uraian]);
                            $currentProgramId = $program->id;
                            $this->command->info("  [PROG] Set Program: {$uraian}");
                        }
                        continue;
                    }

                    // 3. Deteksi PROGRAM (Sub-header tanpa kode akun, anggaran 0)
                    // Contoh: "Gaji", "Fasilitas"
                    if (empty($kodeAkun) && !empty($uraian) && $anggaranTotal == 0) {
                        $program = ProgramKerja::firstOrCreate(['nama_program' => $uraian]);
                        $currentProgramId = $program->id;
                        $this->command->info("  [PROG] Set Program: {$uraian}");
                        continue;
                    }

                    // 4. Deteksi DATA ANGGARAN (Ada Anggaran > 0)
                    if ($anggaranTotal > 0 && $currentDepartemenId) {
                        
                        // Jika belum ada program, buat program 'Umum'
                        if (!$currentProgramId) {
                            $progUmum = ProgramKerja::firstOrCreate(['nama_program' => 'Operasional Umum']);
                            $currentProgramId = $progUmum->id;
                        }

                        // Logika Kode Akun
                        $finalKodeAkun = $kodeAkun;
                        if (empty($finalKodeAkun)) {
                            // Generate dummy kode akun: 5-01-99-XXX
                            $randomSuffix = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
                            $finalKodeAkun = $lastKodeAkunPrefix . '-99-' . $randomSuffix;
                            $this->command->warn("    ! Baris '{$uraian}' tidak ada kode. Pakai dummy: {$finalKodeAkun}");
                        } else {
                            // Update prefix terakhir
                             preg_match('/^([45]-\d{4})/', $finalKodeAkun, $matches);
                             if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];
                        }

                        // Buat/Ambil Akun GL
                        $tipeAkun = str_starts_with($finalKodeAkun, '4') ? 'Pendapatan' : 'Biaya';
                        $akun = AkunGl::firstOrCreate(
                            ['kode_akun' => $finalKodeAkun],
                            ['nama_akun' => $uraian, 'tipe_akun' => $tipeAkun]
                        );

                        // Simpan Budget
                        // Cek duplikat dulu
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
                            $this->command->getOutput()->writeln("<info>      + Budget OK: {$uraian} (Rp " . number_format($anggaranTotal) . ")</info>");
                        }
                    }
                } // end while
                
                fclose($handle);
            }
        });

        $this->command->info("Selesai. Cek tabel tbl_budget_master.");
    }

    private function cleanNumeric($value): float
    {
        if (is_null($value) || $value === '') return 0;
        $value = trim($value); 
        $value = str_replace('.', '', $value); // Hapus titik ribuan (Indonesian format)
        $value = str_replace(',', '.', $value); // Ganti koma desimal jadi titik
        $value = preg_replace('/[^0-9\.-]/', '', $value); 
        return (float) $value;
    }
}