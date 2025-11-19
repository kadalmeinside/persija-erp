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
    // Nama file CSV dan tahun anggaran sebagai properti kelas
    private $fileName = "anggaran.csv";
    private $tahunAnggaran = 2025;

    // Array untuk memetakan kolom pacing ke field database
    private $monthFields = [
        'pacing_jan', 'pacing_feb', 'pacing_mar', 'pacing_apr', 'pacing_mei', 'pacing_jun',
        'pacing_jul', 'pacing_agu', 'pacing_sep', 'pacing_okt', 'pacing_nov', 'pacing_des'
    ];

    public function run(): void
    {
        $absolutePath = storage_path("app/seeders/{$this->fileName}");

        if (!file_exists($absolutePath)) {
            $this->command->error("ERROR: File tidak ditemukan di {$absolutePath}");
            return;
        }

        $this->command->info("Memulai parsing file: {$this->fileName} untuk Tahun {$this->tahunAnggaran}");

        DB::transaction(function () use ($absolutePath) {
            
            $currentDepartemenId = null;
            $currentProgramId = null;
            $lastKodeAkunPrefix = '5-0000'; // Default

            if (($handle = fopen($absolutePath, "r")) !== false) {
                
                $rowNumber = 0;
                while (($row = fgetcsv($handle, 0, ';')) !== false) {
                    $rowNumber++;
                    
                    // Lewati baris kosong (tidak ada data sama sekali di row)
                    if (empty(implode('', $row)) || count($row) < 3) continue;

                    // Pindahkan logika ke method terpisah
                    $result = $this->processRow(
                        $row, 
                        $rowNumber, 
                        $currentDepartemenId, 
                        $currentProgramId, 
                        $lastKodeAkunPrefix
                    );

                    if (!is_array($result)) {
                        $this->command->warn("Baris {$rowNumber}: Gagal memproses (Invalid Result).");
                        continue;
                    }
                    
                    // Update state variables
                    $currentDepartemenId = $result['current_departemen_id'] ?? $currentDepartemenId;
                    $currentProgramId = $result['current_program_id'] ?? $currentProgramId;
                    $lastKodeAkunPrefix = $result['last_kode_akun_prefix'] ?? $lastKodeAkunPrefix;

                } // end while
                
                fclose($handle);
                $this->command->info("\nSelesai parsing.");
            }
        });
    }

    /**
     * Logika Utama dengan Hierarki Pasti (Strict Hierarchy)
     */
    private function processRow(
        array $row, 
        int $rowNumber, 
        ?int $currentDepartemenId, 
        ?int $currentProgramId, 
        string $lastKodeAkunPrefix
    ): array
    {
        // 1. SANITASI DATA
        $kodeAkun = trim($row[0] ?? '');
        $uraian = trim($row[1] ?? '');
        // Hapus karakter aneh/tersembunyi
        $kodeAkun = preg_replace('/[\x00-\x1F\x7F\xc2\xa0]/', '', $kodeAkun);
        $uraian = preg_replace('/[\x00-\x1F\x7F\xc2\xa0]/', '', $uraian);
        
        $anggaranTotal = $this->cleanNumeric($row[2] ?? 0);

        // 2. ABAIKAN HEADER / SAMPAH
        $ignoredKeywords = ['BIAYA', 'PENDAPATAN', 'KODE AKUN', 'ANGGARAN TAHUN 2025'];
        if (in_array(strtoupper($kodeAkun), $ignoredKeywords) || str_contains(strtoupper($kodeAkun), 'DUMMY')) {
             return compact('currentDepartemenId', 'currentProgramId', 'lastKodeAkunPrefix');
        }

        // 3. DETEKSI TIPE BERDASARKAN POLA (REGEX)
        
        // Pola Departemen: 5-01. (Angka-Dash-2Digit-Titik)
        $isDeptPattern = preg_match('/^[45]-\d{2}\./', $kodeAkun);
        
        // Pola Akun GL: 5-0101-01 (Angka-Dash-4Digit-Dash-2Digit)
        $isGlPattern = preg_match('/^[45]-\d{4}-\d{2}/', $kodeAkun);


        // --- KASUS 1: DEPARTEMEN ---
        if ($isDeptPattern) {
            $namaDeptClean = preg_replace('/^[45]-\d{2}\.\s*/', '', $uraian);
            if (empty($namaDeptClean)) {
                 $namaDeptClean = preg_replace('/^[45]-\d{2}\.\s*/', '', $kodeAkun);
            }

            $dept = Departemen::firstOrCreate(['nama_departemen' => $namaDeptClean]);
            $currentDepartemenId = $dept->id;
            $currentProgramId = null; // Reset program setiap ganti Dept
            
            // Simpan prefix (misal 5-01)
            preg_match('/^([45]-\d{2})/', $kodeAkun, $matches);
            if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];

            $this->command->info("  [DEPT] {$namaDeptClean}");
            
            return compact('currentDepartemenId', 'currentProgramId', 'lastKodeAkunPrefix');
        }

        // --- KASUS 2: AKUN GL (DATA ANGGARAN) ---
        // Cek ini SEBELUM Program Kerja, supaya kode 5-0101-01 tidak dianggap nama Program
        if ($isGlPattern) {
            
            // Validasi State
            if (!$currentDepartemenId) {
                $dept = Departemen::firstOrCreate(['nama_departemen' => 'UMUM']);
                $currentDepartemenId = $dept->id;
            }
            if (!$currentProgramId) {
                $progUmum = ProgramKerja::firstOrCreate(['nama_program' => 'Operasional Umum']);
                $currentProgramId = $progUmum->id;
            }

            // Update prefix
            preg_match('/^([45]-\d{4})/', $kodeAkun, $matches);
            if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];

            // Simpan Akun
            $tipeAkun = str_starts_with($kodeAkun, '4') ? 'Pendapatan' : 'Biaya';
            $akun = AkunGl::firstOrCreate(
                ['kode_akun' => $kodeAkun],
                ['nama_akun' => $uraian, 'tipe_akun' => $tipeAkun]
            );

            // Simpan Budget
            $exists = BudgetMaster::where('tahun', $this->tahunAnggaran)
                ->where('id_departemen', $currentDepartemenId)
                ->where('id_akun', $akun->id)
                ->where('id_program', $currentProgramId)
                ->exists();

            if (!$exists) {
                $budgetData = [
                    'tahun' => $this->tahunAnggaran,
                    'id_departemen' => $currentDepartemenId,
                    'id_akun' => $akun->id,
                    'id_program' => $currentProgramId,
                    'anggaran_total_tahun' => $anggaranTotal,
                    'anggaran_terikat_ytd' => 0,
                    'anggaran_realisasi_ytd' => 0,
                ];

                foreach ($this->monthFields as $index => $field) {
                    $csvIndex = $index + 3; 
                    $budgetData[$field] = $this->cleanNumeric($row[$csvIndex] ?? 0);
                }

                BudgetMaster::create($budgetData);
                $this->command->getOutput()->write('.'); 
            }

            return compact('currentDepartemenId', 'currentProgramId', 'lastKodeAkunPrefix');
        }

        // --- KASUS 3: PROGRAM KERJA (FALLBACK) ---
        // Jika bukan Departemen, bukan Akun GL, tapi ada isinya -> Pasti Header Program
        // Contoh: "Gaji & Tunjangan", "Operasional Kantor"
        if (!empty($kodeAkun) || !empty($uraian)) {
            
            // Ambil nama program dari Uraian, jika kosong ambil dari Kode Akun
            $programName = !empty($uraian) ? $uraian : $kodeAkun;

            $program = ProgramKerja::firstOrCreate(['nama_program' => $programName]);
            $currentProgramId = $program->id;
            
            $this->command->info("    [PROG] {$programName}");

            return compact('currentDepartemenId', 'currentProgramId', 'lastKodeAkunPrefix');
        }

        // Jika baris kosong atau tidak dikenali
        return compact('currentDepartemenId', 'currentProgramId', 'lastKodeAkunPrefix');
    }

    private function cleanNumeric($value): float
    {
        if (is_null($value) || $value === '') return 0.0;
        $value = trim($value); 
        $value = str_replace('.', '', $value); 
        $value = str_replace(',', '.', $value); 
        $value = preg_replace('/[^0-9\.-]/', '', $value); 
        return (float) $value;
    }
}