<?php

namespace App\Imports;

use App\Models\Departemen;
use App\Models\ProgramKerja;
use App\Models\AkunGl;
use App\Models\PosAnggaran;
use App\Models\BudgetMaster;
use App\Models\BudgetDetail;
use App\Models\PeriodeAnggaran;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class BudgetImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $rows
    */
    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                // Laravel Excel slugifies headers: 
                // 'Nama Departemen' -> 'nama_departemen'
                // 'Nama Program Kerja' -> 'nama_program_kerja'
                // 'Kode COA (Akun Biaya)' -> 'kode_coa_akun_biaya'
                // 'Anggaran_Jan' -> 'anggaran_jan'
                
                $tahunStr = $row['tahun'] ?? null;
                $departemenStr = $row['nama_departemen'] ?? $row['departemen'] ?? null;
                $programKerjaStr = $row['nama_program_kerja'] ?? $row['program_kerja'] ?? null;
                $kodeAkunStr = $row['kode_coa_akun_biaya'] ?? $row['kode_coa_akun'] ?? $row['kode_akun'] ?? null;

                // Skip empty rows
                if (!$tahunStr || !$departemenStr || !$programKerjaStr || !$kodeAkunStr) {
                    continue;
                }

                $tahun = trim($tahunStr);
                $namaDepartemen = trim($departemenStr);
                $namaProgram = trim($programKerjaStr);
                $kodeAkun = trim($kodeAkunStr);

                // Find Periode Anggaran
                $periode = PeriodeAnggaran::where('nama_periode', 'like', "%{$tahun}%")
                    ->orWhere('tanggal_mulai', 'like', "{$tahun}-%")
                    ->first();

                if (!$periode) {
                    throw new \Exception("Periode anggaran untuk tahun {$tahun} tidak ditemukan di database.");
                }

                // Find or Create Departemen
                $departemen = Departemen::firstOrCreate(
                    ['nama_departemen' => $namaDepartemen],
                    ['is_active' => true]
                );

                // Find or Create Program Kerja
                $programKerja = ProgramKerja::firstOrCreate(
                    [
                        'id_departemen' => $departemen->id,
                        'nama_program' => $namaProgram
                    ],
                    [
                        'deskripsi' => 'Di-import dari Excel',
                        'is_active' => true
                    ]
                );

                // Determine tipe_akun based on standard accounting practices (First Digit)
                $firstDigit = substr($kodeAkun, 0, 1);
                $tipeAkun = 'Biaya'; // Default fallback
                if (in_array($firstDigit, ['1'])) {
                    $tipeAkun = 'Aset';
                } elseif (in_array($firstDigit, ['2'])) {
                    $tipeAkun = 'Utang';
                } elseif (in_array($firstDigit, ['3'])) {
                    $tipeAkun = 'Modal';
                } elseif (in_array($firstDigit, ['4', '7'])) {
                    $tipeAkun = 'Pendapatan';
                } elseif (in_array($firstDigit, ['5', '6', '8', '9'])) {
                    $tipeAkun = 'Biaya';
                }

                // Find Akun GL or Create if not exists
                $namaAkunStr = $row['nama_akun_biaya'] ?? $row['nama_akun'] ?? $row['nama_coa'] ?? "Akun {$kodeAkun}";
                $akunGl = AkunGl::firstOrCreate(
                    ['kode_akun' => $kodeAkun],
                    [
                        'nama_akun' => trim($namaAkunStr),
                        'tipe_akun' => $tipeAkun,
                        'is_active' => true
                    ]
                );

                // Find or Create Pos Anggaran
                $posAnggaran = PosAnggaran::firstOrCreate(
                    [
                        'id_program_kerja' => $programKerja->id,
                        'id_akun_gl' => $akunGl->id
                    ],
                    [
                        'is_active' => true
                    ]
                );

                // Extract pacing per month (anggaran_jan, anggaran_feb, dll)
                $months = [
                    1 => $row['anggaran_jan'] ?? $row['jan'] ?? $row['bulan_1'] ?? $row['1'] ?? 0,
                    2 => $row['anggaran_feb'] ?? $row['feb'] ?? $row['bulan_2'] ?? $row['2'] ?? 0,
                    3 => $row['anggaran_mar'] ?? $row['mar'] ?? $row['bulan_3'] ?? $row['3'] ?? 0,
                    4 => $row['anggaran_apr'] ?? $row['apr'] ?? $row['bulan_4'] ?? $row['4'] ?? 0,
                    5 => $row['anggaran_mei'] ?? $row['mei'] ?? $row['bulan_5'] ?? $row['5'] ?? 0,
                    6 => $row['anggaran_jun'] ?? $row['jun'] ?? $row['bulan_6'] ?? $row['6'] ?? 0,
                    7 => $row['anggaran_jul'] ?? $row['jul'] ?? $row['bulan_7'] ?? $row['7'] ?? 0,
                    8 => $row['anggaran_ags'] ?? $row['ags'] ?? $row['bulan_8'] ?? $row['8'] ?? 0,
                    9 => $row['anggaran_sep'] ?? $row['sep'] ?? $row['bulan_9'] ?? $row['9'] ?? 0,
                    10 => $row['anggaran_okt'] ?? $row['okt'] ?? $row['bulan_10'] ?? $row['10'] ?? 0,
                    11 => $row['anggaran_nov'] ?? $row['nov'] ?? $row['bulan_11'] ?? $row['11'] ?? 0,
                    12 => $row['anggaran_des'] ?? $row['des'] ?? $row['bulan_12'] ?? $row['12'] ?? 0,
                ];

                $totalAnggaran = 0;
                foreach ($months as $m => $val) {
                    $val = str_replace([',', '.'], '', (string)$val); // clean formatting if any
                    $months[$m] = (float)$val;
                    $totalAnggaran += $months[$m];
                }

                // Find or Create Budget Master
                $budgetMaster = BudgetMaster::firstOrCreate(
                    [
                        'id_periode_anggaran' => $periode->id,
                        'id_pos_anggaran' => $posAnggaran->id,
                    ],
                    [
                        'anggaran_total_tahun' => $totalAnggaran,
                        'anggaran_terikat_ytd' => 0,
                        'anggaran_realisasi_ytd' => 0,
                    ]
                );

                // If already exists, we UPDATE it (Overwrite)
                if (!$budgetMaster->wasRecentlyCreated) {
                    // Overwrite total
                    $budgetMaster->update([
                        'anggaran_total_tahun' => $totalAnggaran
                    ]);
                }

                // Insert / Update Budget Detail
                foreach ($months as $bulan => $nominal) {
                    BudgetDetail::updateOrCreate(
                        [
                            'id_budget_master' => $budgetMaster->id,
                            'bulan' => $bulan,
                            'tahun' => substr($periode->tanggal_mulai, 0, 4) // simple year extraction
                        ],
                        [
                            'nominal_pacing' => $nominal
                        ]
                    );
                }
            }
        });
    }
}
