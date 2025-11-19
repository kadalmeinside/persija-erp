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
        // Sesuaikan nama file ini jika perlu
        $fileName = "anggaran.csv"; 
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
                // Gunakan titik koma (;) sebagai delimiter
                while (($row = fgetcsv($handle, 0, ';')) !== false) {
                    $rowNumber++;
                    
                    // Lewati baris kosong
                    if (empty(implode('', $row)) || count($row) < 3) continue;

                    $kodeAkun = trim($row[0] ?? '');
                    $uraian = trim($row[1] ?? '');
                    // Bersihkan angka anggaran
                    $anggaranTotal = $this->cleanNumeric($row[2] ?? 0);

                    // Abaikan header tabel
                    if (in_array(strtoupper($kodeAkun), ['BIAYA', 'PENDAPATAN', 'KODE AKUN'])) continue;
                    if (in_array(strtoupper($uraian), ['BIAYA', 'PENDAPATAN'])) continue;
                    
                    // --- LOGIKA DETEKSI ---

                    // 1. DEPARTEMEN (Ciri: Kode '5-XX.' ATAU Uraian ada tapi Anggaran 0 & Kode Akun Kosong)
                    // Kita perlonggar logika ini
                    $isDepartemen = false;
                    if (preg_match('/^[45]-\d{2}\./', $kodeAkun)) {
                        $isDepartemen = true;
                    } elseif (empty($kodeAkun) && !empty($uraian) && $anggaranTotal == 0 && $currentDepartemenId == null) {
                         // Baris pertama file biasanya Judul / PT, kita anggap bukan departemen kecuali eksplisit
                         // Tapi jika di tengah file ada baris tanpa kode akun & tanpa anggaran, itu mungkin Header Program atau Dept baru
                    }

                    if ($isDepartemen) {
                        $namaDept = !empty($kodeAkun) ? $kodeAkun : $uraian;
                        // Bersihkan nama (hapus '5-01. ')
                        $namaDeptClean = preg_replace('/^[45]-\d{2}\.\s*/', '', $namaDept);
                        
                        $dept = Departemen::firstOrCreate(['nama_departemen' => $namaDeptClean]);
                        $currentDepartemenId = $dept->id;
                        $currentProgramId = null; // Reset program karena ganti departemen
                        
                        // Ambil prefix (misal 5-01)
                        preg_match('/^([45]-\d{2})/', $kodeAkun, $matches);
                        if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];

                        $this->command->info("  [DEPT] {$namaDeptClean}");
                        continue;
                    }

                    // 2. PROGRAM KERJA (Ciri: Kode Akun Kosong, Ada Uraian, Anggaran 0)
                    // Ini asumsi untuk baris seperti "Gaji & Tunjangan"
                    if (empty($kodeAkun) && !empty($uraian) && $anggaranTotal == 0) {
                        $program = ProgramKerja::firstOrCreate(['nama_program' => $uraian]);
                        $currentProgramId = $program->id;
                        $this->command->info("    [PROG] {$uraian}");
                        continue;
                    }

                    // 3. DATA ANGGARAN (Ciri: Ada Anggaran > 0)
                    if ($anggaranTotal > 0) {
                        
                        // Pastikan Dept ada (jika tidak, mungkin file dimulai tanpa header dept yang benar)
                        if (!$currentDepartemenId) {
                            // Fallback: Buat departemen default jika belum terdeteksi
                            $dept = Departemen::firstOrCreate(['nama_departemen' => 'UMUM']);
                            $currentDepartemenId = $dept->id;
                        }

                        // Pastikan Program ada (jika tidak, buat program 'Umum')
                        if (!$currentProgramId) {
                            $progUmum = ProgramKerja::firstOrCreate(['nama_program' => 'Operasional Umum']);
                            $currentProgramId = $progUmum->id;
                        }

                        // Tentukan Kode Akun
                        $finalKodeAkun = $kodeAkun;
                        if (empty($finalKodeAkun)) {
                            // Generate dummy jika kosong
                            $randomSuffix = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
                            $finalKodeAkun = $lastKodeAkunPrefix . '-99-' . $randomSuffix;
                        } else {
                            // Update prefix terakhir
                             preg_match('/^([45]-\d{4})/', $finalKodeAkun, $matches);
                             if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];
                        }

                        // Buat Akun GL
                        $tipeAkun = str_starts_with($finalKodeAkun, '4') ? 'Pendapatan' : 'Biaya';
                        $akun = AkunGl::firstOrCreate(
                            ['kode_akun' => $finalKodeAkun],
                            ['nama_akun' => $uraian, 'tipe_akun' => $tipeAkun]
                        );

                        // Simpan Budget
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
                            // Tanda sukses
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
        $value = str_replace('.', '', $value); // Hapus titik ribuan
        $value = str_replace(',', '.', $value); // Ganti koma desimal jadi titik
        $value = preg_replace('/[^0-9\.-]/', '', $value); 
        return (float) $value;
    }
}