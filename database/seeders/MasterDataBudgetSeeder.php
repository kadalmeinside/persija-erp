<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Departemen;
use App\Models\ProgramKerja;
use App\Models\AkunGl;
use App\Models\BudgetMaster;
use App\Models\BudgetDetail;
use App\Models\PeriodeAnggaran;
use Carbon\Carbon;

class MasterDataBudgetSeeder extends Seeder
{
    private $fileName = "anggaran.csv"; 
    
    // Konfigurasi Periode (Bisa disesuaikan nanti)
    private $periodeName = "Tahun Anggaran 2025";
    private $startDate = "2025-01-01";
    private $endDate = "2025-12-31";

    public function run(): void
    {
        $absolutePath = storage_path("app/seeders/{$this->fileName}");

        if (!file_exists($absolutePath)) {
            $this->command->error("ERROR: File tidak ditemukan di {$absolutePath}");
            return;
        }

        DB::transaction(function () use ($absolutePath) {
            
            // 1. Buat/Ambil Periode Anggaran
            $periode = PeriodeAnggaran::firstOrCreate(
                ['nama_periode' => $this->periodeName],
                [
                    'tanggal_mulai' => $this->startDate,
                    'tanggal_selesai' => $this->endDate,
                    'is_active' => true
                ]
            );
            
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

                    if (in_array(strtoupper($kodeAkun), ['BIAYA', 'PENDAPATAN', 'KODE AKUN', 'ANGGARAN TAHUN 2025'])) continue;
                    if (in_array(strtoupper($uraian), ['BIAYA', 'PENDAPATAN'])) continue;

                    // --- DETEKSI DEPARTEMEN & PROGRAM (Sama seperti sebelumnya) ---
                    $isDepartemen = preg_match('/^[45]-\d{2}\./', $kodeAkun);
                    if ($isDepartemen) {
                        $namaDeptClean = preg_replace('/^[45]-\d{2}\.\s*/', '', (!empty($kodeAkun) ? $kodeAkun : $uraian));
                        $dept = Departemen::firstOrCreate(['nama_departemen' => $namaDeptClean]);
                        $currentDepartemenId = $dept->id;
                        $currentProgramId = null;
                        preg_match('/^([45]-\d{2})/', $kodeAkun, $matches);
                        if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];
                        $this->command->info("  [DEPT] {$namaDeptClean}");
                        continue;
                    }

                    if (!$isDepartemen && !preg_match('/^[45]-\d{4}-\d{2}/', $kodeAkun) && $anggaranTotal == 0 && !empty($uraian)) {
                        $programName = $uraian;
                        if (!empty($kodeAkun)) $programName = $kodeAkun;
                        $program = ProgramKerja::firstOrCreate(['nama_program' => $programName]);
                        $currentProgramId = $program->id;
                        $this->command->info("    [PROG] {$programName}");
                        continue;
                    }

                    // --- PROSES AKUN & BUDGET VERTIKAL ---
                    $isAkun = ($anggaranTotal > 0) || preg_match('/^[45]-\d{4}-\d{2}/', $kodeAkun);

                    if ($isAkun && $currentDepartemenId && $currentProgramId) {
                        
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

                        // Cek apakah budget header sudah ada
                        $budgetHeader = BudgetMaster::firstOrCreate(
                            [
                                'id_periode_anggaran' => $periode->id,
                                'id_departemen' => $currentDepartemenId,
                                'id_akun' => $akun->id,
                                'id_program' => $currentProgramId,
                            ],
                            [
                                'anggaran_total_tahun' => $anggaranTotal,
                                'anggaran_terikat_ytd' => 0,
                                'anggaran_realisasi_ytd' => 0,
                            ]
                        );

                        // --- INSERT DETAIL PACING (VERTIKAL) ---
                        // Asumsi CSV kolom 3 = Bulan ke-1 dari Periode
                        $startMonth = Carbon::parse($this->startDate);
                        
                        for ($i = 0; $i < 12; $i++) {
                            $csvIndex = $i + 3; // Kolom CSV mulai dari index 3
                            $nominal = $this->cleanNumeric($row[$csvIndex] ?? 0);
                            
                            // Hitung Bulan & Tahun kalender berdasarkan offset
                            $currentDate = $startMonth->copy()->addMonths($i);
                            
                            BudgetDetail::updateOrCreate(
                                [
                                    'id_budget_master' => $budgetHeader->id,
                                    'bulan' => $currentDate->month,
                                    'tahun' => $currentDate->year,
                                ],
                                [
                                    'nominal_pacing' => $nominal
                                ]
                            );
                        }
                        $this->command->getOutput()->write('.'); 
                    }
                } 
                fclose($handle);
                $this->command->info("\nSelesai parsing.");
            }
        });
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