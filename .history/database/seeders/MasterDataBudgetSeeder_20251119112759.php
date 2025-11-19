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
                    
                    // Lewati baris kosong
                    if (empty(implode('', $row)) || count($row) < 3) continue;

                    $result = $this->processRow(
                        $row, 
                        $rowNumber, 
                        $currentDepartemenId, 
                        $currentProgramId, 
                        $lastKodeAkunPrefix
                    );

                    if (!is_array($result)) {
                        $this->command->warn("Baris {$rowNumber}: Gagal memproses.");
                        continue;
                    }
                    
                    // Update state variables (Perbaikan Key Snake Case)
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
     * Logika Utama dengan Return Array Eksplisit
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
        $kodeAkun = preg_replace('/[\x00-\x1F\x7F\xc2\xa0]/', '', $kodeAkun);
        $uraian = preg_replace('/[\x00-\x1F\x7F\xc2\xa0]/', '', $uraian);
        
        $anggaranTotal = $this->cleanNumeric($row[2] ?? 0);

        // Helper untuk return format array yang konsisten
        $buildResult = fn($deptId, $progId, $prefix) => [
            'current_departemen_id' => $deptId,
            'current_program_id' => $progId,
            'last_kode_akun_prefix' => $prefix
        ];

        // 2. ABAIKAN HEADER
        $ignoredKeywords = ['BIAYA', 'PENDAPATAN', 'KODE AKUN', 'ANGGARAN TAHUN 2025'];
        if (in_array(strtoupper($kodeAkun), $ignoredKeywords) || str_contains(strtoupper($kodeAkun), 'DUMMY')) {
             return $buildResult($currentDepartemenId, $currentProgramId, $lastKodeAkunPrefix);
        }

        // 3. DETEKSI TIPE BERDASARKAN POLA
        $isDeptPattern = preg_match('/^[45]-\d{2}\./', $kodeAkun);
        $isGlPattern = preg_match('/^[45]-\d{4}-\d{2}/', $kodeAkun);

        // --- KASUS 1: DEPARTEMEN ---
        if ($isDeptPattern) {
            $namaDeptClean = preg_replace('/^[45]-\d{2}\.\s*/', '', $uraian);
            if (empty($namaDeptClean)) {
                 $namaDeptClean = preg_replace('/^[45]-\d{2}\.\s*/', '', $kodeAkun);
            }

            $dept = Departemen::firstOrCreate(['nama_departemen' => $namaDeptClean]);
            $currentDepartemenId = $dept->id;
            $currentProgramId = null; // Reset program
            
            preg_match('/^([45]-\d{2})/', $kodeAkun, $matches);
            if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];

            $this->command->info("  [DEPT] {$namaDeptClean}");
            
            return $buildResult($currentDepartemenId, $currentProgramId, $lastKodeAkunPrefix);
        }

        // --- KASUS 2: AKUN GL ---
        if ($isGlPattern) {
            if (!$currentDepartemenId) {
                $dept = Departemen::firstOrCreate(['nama_departemen' => 'UMUM']);
                $currentDepartemenId = $dept->id;
            }

            if (!$currentProgramId) {
                $this->command->error("  [ERROR] Baris {$rowNumber}: SKIP. Akun GL '{$kodeAkun}' - '{$uraian}' tidak memiliki Program Kerja Induk.");
                return $buildResult($currentDepartemenId, $currentProgramId, $lastKodeAkunPrefix);
            }

            preg_match('/^([45]-\d{4})/', $kodeAkun, $matches);
            if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];

            $tipeAkun = str_starts_with($kodeAkun, '4') ? 'Pendapatan' : 'Biaya';
            $akun = AkunGl::firstOrCreate(
                ['kode_akun' => $kodeAkun],
                ['nama_akun' => $uraian, 'tipe_akun' => $tipeAkun]
            );

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

            return $buildResult($currentDepartemenId, $currentProgramId, $lastKodeAkunPrefix);
        }

        // --- KASUS 3: PROGRAM KERJA (FALLBACK) ---
        if (!empty($kodeAkun) || !empty($uraian)) {
            $programName = !empty($uraian) ? $uraian : $kodeAkun;

            $program = ProgramKerja::firstOrCreate(['nama_program' => $programName]);
            $currentProgramId = $program->id;
            
            $this->command->info("    [PROG] {$programName}");

            return $buildResult($currentDepartemenId, $currentProgramId, $lastKodeAkunPrefix);
        }

        return $buildResult($currentDepartemenId, $currentProgramId, $lastKodeAkunPrefix);
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