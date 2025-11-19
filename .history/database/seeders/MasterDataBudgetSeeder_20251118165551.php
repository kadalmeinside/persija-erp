<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
// Hapus: use Illuminate\Support\Facades\Storage; 
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
        // PERBAIKAN 1: Gunakan absolute path langsung ke storage/app/seeders
        // Pastikan nama file di sini PERSIS sama dengan file di folder Anda
        $fileName = "anggaran.csv"; 
        $absolutePath = storage_path("app/seeders/{$fileName}");
        
        $tahunAnggaran = 2025; 

        // Debugging: Cetak path yang dicari agar Anda bisa verifikasi di terminal
        $this->command->info("Mencari file di: {$absolutePath}");

        if (!file_exists($absolutePath)) {
            $this->command->error("ERROR: File tidak ditemukan!");
            $this->command->warn("Pastikan file ada di folder: " . storage_path("app/seeders/"));
            $this->command->warn("Dan namanya persis: {$fileName}");
            return;
        }

        $this->command->info("File ditemukan! Memulai parsing...");

        DB::transaction(function () use ($absolutePath, $tahunAnggaran) {
            
            // Variabel untuk melacak konteks data saat ini (karena CSV berjenjang)
            $currentDepartemenId = null;
            $currentProgramId = null;
            $currentTipeAkun = null; 
            $skipRows = 5; // Lewati 5 baris header awal
            $lastKodeAkunPrefix = '5-0101'; // Default prefix jika kode akun kosong

            // Gunakan fopen standar PHP untuk kontrol lebih baik
            if (($handle = fopen($absolutePath, "r")) !== false) {
                
                // PERBAIKAN 2: Gunakan delimiter ';' sesuai file Anda
                while (($row = fgetcsv($handle, 1000, ';')) !== false) {
                    
                    // Lewati baris header awal
                    if ($skipRows > 0) {
                        $skipRows--;
                        continue;
                    }

                    // Lewati baris kosong
                    if (empty(implode('', $row)) || count($row) < 3) {
                        continue;
                    }

                    $kodeAkun = trim($row[0] ?? '');
                    $uraian = trim($row[1] ?? '');
                    $anggaranTotal = $this->cleanNumeric($row[2] ?? 0);

                    // 1. Cek Header Tipe Akun (BIAYA / PENDAPATAN)
                    if (strtoupper($kodeAkun) == 'BIAYA' || strtoupper($uraian) == 'BIAYA') {
                        $currentTipeAkun = 'Biaya';
                        $this->command->info("Mengganti Tipe Akun ke: BIAYA");
                        continue;
                    }
                    if (strtoupper($kodeAkun) == 'PENDAPATAN' || strtoupper($uraian) == 'PENDAPATAN') {
                        $currentTipeAkun = 'Pendapatan';
                        $this->command->info("Mengganti Tipe Akun ke: PENDAPATAN");
                        continue;
                    }
                    
                    // Lewati baris judul kolom
                    if (strtoupper($kodeAkun) == 'KODE AKUN') {
                        continue;
                    }

                    // 2. Cek Header Departemen / Program
                    // Logika: Jika kode akun format '5-XX.' ATAU (anggaran 0 DAN uraian ada)
                    if ((preg_match('/^[45]-(\d{2})\./', $kodeAkun, $matches) || !empty($uraian)) && $anggaranTotal == 0) {
                        
                        // Jika kode akun kosong tapi ada uraian -> Kemungkinan Program
                        if (empty($kodeAkun) && !empty($uraian)) {
                            $program = ProgramKerja::firstOrCreate(['nama_program' => $uraian]);
                            $currentProgramId = $program->id;
                            $this->command->getOutput()->writeln("<comment>  -> Mengganti Program ke: {$uraian}</comment>");
                        }
                        // Jika ada kode akun -> Kemungkinan Departemen
                        else {
                            $namaDept = !empty($kodeAkun) ? $kodeAkun : $uraian;
                            $dept = Departemen::firstOrCreate(['nama_departemen' => $namaDept]);
                            $currentDepartemenId = $dept->id;
                            $currentProgramId = null; // Reset program saat ganti departemen
                            
                            // Simpan prefix untuk kode akun dummy nanti
                            if(isset($matches[1])) {
                                 $lastKodeAkunPrefix = '5-' . $matches[1];
                            }
                            
                            $this->command->info("Mengganti Departemen ke: {$namaDept}");
                        }
                        continue;
                    }

                    // 3. Cek Baris Data Utama (Punya Kode Akun & Anggaran)
                    if (preg_match('/^([45]-\d{4})-\d{2}$/', $kodeAkun, $matches) && $currentDepartemenId && $currentProgramId) {
                        
                        $lastKodeAkunPrefix = $matches[1]; 
                        
                        $akun = $this->createAkun($kodeAkun, $uraian);
                        $this->createBudgetEntry($row, $tahunAnggaran, $currentDepartemenId, $currentProgramId, $akun->id, $uraian);
                    }
                    // 4. Cek Baris Data TANPA Kode Akun (Dummy)
                    // Kasus: Baris seperti "Tunjangan Hari Raya" yang tidak punya kode akun di CSV
                    else if (empty($kodeAkun) && !empty($uraian) && $anggaranTotal > 0 && $currentDepartemenId && $currentProgramId) {
                        
                        $this->command->warn("    ! Baris '{$uraian}' tidak memiliki Kode Akun. Membuat Kode Akun dummy...");
                        
                        // Generate kode akun dummy agar data tetap masuk
                        $dummyKodeAkun = $lastKodeAkunPrefix . '-99'; 
                        $i = 99;
                        // Pastikan kode unik
                        while(AkunGl::where('kode_akun', $dummyKodeAkun)->exists()) {
                            $i--;
                            $dummyKodeAkun = $lastKodeAkunPrefix . '-' . $i;
                            if ($i < 90) break; 
                        }
                        
                        $akun = $this->createAkun($dummyKodeAkun, $uraian, 'Biaya');
                        $this->createBudgetEntry($row, $tahunAnggaran, $currentDepartemenId, $currentProgramId, $akun->id, $uraian);
                    }

                } // end while
                
                fclose($handle);
            }

        }); // end transaction

        $this->command->info("Proses Seeding Master Data dan Anggaran selesai.");
    }
    
    // Helper untuk membuat atau mengambil Akun GL
    private function createAkun($kodeAkun, $uraian, $defaultTipe = 'Biaya'): AkunGl
    {
        $tipeAkun = $defaultTipe;
        if (str_starts_with($kodeAkun, '4-')) {
            $tipeAkun = 'Pendapatan';
        } else if (str_starts_with($kodeAkun, '5-1001')) {
            $tipeAkun = 'Biaya Modal'; 
        }

        return AkunGl::firstOrCreate(
            ['kode_akun' => $kodeAkun],
            ['nama_akun' => $uraian, 'tipe_akun' => $tipeAkun]
        );
    }
    
    // Helper untuk memasukkan data ke tabel BudgetMaster
    private function createBudgetEntry(array $row, int $tahunAnggaran, int $currentDepartemenId, int $currentProgramId, int $akunId, string $uraian): void
    {
        // Cek duplikat sebelum insert untuk menghindari error Unique Constraint
        $exists = BudgetMaster::where('tahun', $tahunAnggaran)
            ->where('id_departemen', $currentDepartemenId)
            ->where('id_akun', $akunId)
            ->where('id_program', $currentProgramId)
            ->exists();

        if ($exists) {
            $this->command->warn("    - Anggaran untuk '{$uraian}' sudah ada. Skip.");
            return;
        }

        BudgetMaster::create([
            'tahun' => $tahunAnggaran,
            'id_departemen' => $currentDepartemenId,
            'id_akun' => $akunId,
            'id_program' => $currentProgramId,
            'anggaran_total_tahun' => $this->cleanNumeric($row[2] ?? 0),
            'anggaran_terikat_ytd' => 0,
            'anggaran_realisasi_ytd' => 0,
            // Mapping kolom bulan dari CSV (index 3 s/d 14)
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

    // Fungsi untuk membersihkan format angka Indonesia (titik ribuan, koma desimal)
    private function cleanNumeric($value): float
    {
        if (is_null($value) || $value === '') {
            return 0;
        }
        $value = trim($value); 
        $value = str_replace('.', '', $value); // Hapus titik ribuan
        $value = str_replace(',', '.', $value); // Ganti koma desimal jadi titik
        
        $value = preg_replace('/[^0-9\.-]/', '', $value); // Hapus karakter aneh lainnya

        return (float) $value;
    }
}