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
            $lastKodeAkunPrefix = '5-0000'; // Default, akan di-update saat ketemu Akun GL

            if (($handle = fopen($absolutePath, "r")) !== false) {
                
                $rowNumber = 0;
                while (($row = fgetcsv($handle, 0, ';')) !== false) {
                    $rowNumber++;
                    
                    // Lewati baris kosong atau tidak lengkap
                    if (empty(implode('', $row)) || count($row) < 3) continue;

                    // Pindahkan semua logika pemrosesan ke method terpisah
                    $result = $this->processRow(
                        $row, 
                        $rowNumber, 
                        $currentDepartemenId, 
                        $currentProgramId, 
                        $lastKodeAkunPrefix
                    );

                    // --- FIX: Tambahkan pemeriksaan keamanan sebelum mengakses array keys ---
                    if (!is_array($result)) {
                        $this->command->warn("Baris {$rowNumber}: processRow mengembalikan nilai yang tidak valid (bukan array). Melewatkan pembaruan status.");
                        continue;
                    }
                    // --- END FIX ---
                    
                    // Update state variables setelah diproses.
                    // Menggunakan null-coalescing operator (??) untuk fallback ke nilai lama
                    // jika kunci array hilang secara tak terduga.
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
     * Memproses satu baris data CSV dan mendeteksi tipe data (Dept, Program, atau Akun).
     * @param array $row Data baris CSV.
     * @param int $rowNumber Nomor baris.
     * @param int|null $currentDepartemenId ID Departemen yang sedang aktif.
     * @param int|null $currentProgramId ID Program yang sedang aktif.
     * @param string $lastKodeAkunPrefix Prefix kode akun terakhir yang valid.
     * @return array Status yang diperbarui.
     */
    private function processRow(
        array $row, 
        int $rowNumber, 
        ?int $currentDepartemenId, 
        ?int $currentProgramId, 
        string $lastKodeAkunPrefix
    ): array
    {
        $kodeAkun = trim($row[0] ?? '');
        $uraian = trim($row[1] ?? '');
        $anggaranTotal = $this->cleanNumeric($row[2] ?? 0);

        // Abaikan header tabel yang tidak perlu diproses
        // Menambahkan 'PT. PERSIJA JAKARTA HEBAT (DUMMY V2)' yang mungkin terlewat di awal
        if (in_array(strtoupper($kodeAkun), ['BIAYA', 'PENDAPATAN', 'KODE AKUN', 'ANGGARAN TAHUN 2025']) || str_contains(strtoupper($kodeAkun), 'DUMMY')) {
             return compact('currentDepartemenId', 'currentProgramId', 'lastKodeAkunPrefix');
        }
        
        // --- LOGIKA DETEKSI ---
        
        // Cek pola Kode Akun GL lengkap (contoh: 5-0101-01)
        $isFullGLAccount = preg_match('/^[45]-\d{4}-\d{2}/', $kodeAkun);

        // 1. Deteksi DEPARTEMEN
        // Ciri: Kode '5-XX.' atau '4-XX.'
        $isDepartemen = preg_match('/^[45]-\d{2}\./', $kodeAkun);
        
        if ($isDepartemen) {
            $namaDeptClean = preg_replace('/^[45]-\d{2}\.\s*/', '', $uraian);
            
            // Jika kolom Uraian kosong, gunakan kolom Kode Akun
            if (empty($namaDeptClean)) {
                 $namaDeptClean = preg_replace('/^[45]-\d{2}\.\s*/', '', $kodeAkun);
            }

            $dept = Departemen::firstOrCreate(['nama_departemen' => $namaDeptClean]);
            $currentDepartemenId = $dept->id;
            $currentProgramId = null; // Reset program saat ganti departemen
            
            // Ambil prefix (misal 5-01) untuk keperluan fallback
            preg_match('/^([45]-\d{2})/', $kodeAkun, $matches);
            if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];

            $this->command->info("  [DEPT] {$namaDeptClean}");
            
            return compact('currentDepartemenId', 'currentProgramId', 'lastKodeAkunPrefix');
        }

        // 2. Deteksi PROGRAM KERJA (Sub-Header)
        // Ciri BARU: Bukan Akun GL LENGKAP, Punya Anggaran 0, dan ada Uraian
        if (!$isFullGLAccount && !empty($uraian) && $anggaranTotal == 0) { 
            
            // Gunakan Uraian (Kolom B) sebagai nama program.
            $programName = $uraian;
            
            $program = ProgramKerja::firstOrCreate(['nama_program' => $programName]);
            $currentProgramId = $program->id;
            $this->command->info("    [PROG] {$programName}");
            
            return compact('currentDepartemenId', 'currentProgramId', 'lastKodeAkunPrefix');
        }

        // 3. Deteksi DATA ANGGARAN (AKUN GL)
        // Ciri: Punya Anggaran > 0 ATAU Punya Kode Akun Format Lengkap
        $isAkun = ($anggaranTotal > 0) || $isFullGLAccount;

        if ($isAkun) {
            // **Implementasi Saran #1: Jangan buat akun dummy jika kode akun kosong.**
            $finalKodeAkun = $kodeAkun;
            
            if (empty($finalKodeAkun)) {
                $this->command->warn("Baris {$rowNumber}: Dilewati. Kode Akun kosong pada baris data anggaran: {$uraian}");
                return compact('currentDepartemenId', 'currentProgramId', 'lastKodeAkunPrefix');
            }
            
            // --- VALIDASI CONTEXT ---
            
            // Pastikan Dept ada (jika file dimulai tanpa header dept)
            if (!$currentDepartemenId) {
                $dept = Departemen::firstOrCreate(['nama_departemen' => 'UMUM']);
                $currentDepartemenId = $dept->id;
            }

            // Pastikan Program ada (jika tidak terdeteksi, masuk ke 'Operasional Umum')
            if (!$currentProgramId) {
                $progUmum = ProgramKerja::firstOrCreate(['nama_program' => 'Operasional Umum']);
                $currentProgramId = $progUmum->id;
            }

            // Update prefix terakhir untuk baris selanjutnya
            preg_match('/^([45]-\d{4})/', $finalKodeAkun, $matches);
            if (isset($matches[1])) $lastKodeAkunPrefix = $matches[1];

            // Buat Akun GL
            $tipeAkun = str_starts_with($finalKodeAkun, '4') ? 'Pendapatan' : 'Biaya';
            $akun = AkunGl::firstOrCreate(
                ['kode_akun' => $finalKodeAkun],
                ['nama_akun' => $uraian, 'tipe_akun' => $tipeAkun]
            );

            // Cek duplikat BudgetMaster
            $exists = BudgetMaster::where('tahun', $this->tahunAnggaran)
                ->where('id_departemen', $currentDepartemenId)
                ->where('id_akun', $akun->id)
                ->where('id_program', $currentProgramId)
                ->exists();

            if (!$exists) {
                // **Implementasi Saran #3: Konsolidasi data pacing**
                
                $budgetData = [
                    'tahun' => $this->tahunAnggaran,
                    'id_departemen' => $currentDepartemenId,
                    'id_akun' => $akun->id,
                    'id_program' => $currentProgramId,
                    'anggaran_total_tahun' => $anggaranTotal,
                    'anggaran_terikat_ytd' => 0,
                    'anggaran_realisasi_ytd' => 0,
                ];

                // Loop untuk data pacing (dimulai dari index 3, yaitu kolom D)
                foreach ($this->monthFields as $index => $field) {
                    $csvIndex = $index + 3; 
                    // Pastikan index ada di baris CSV (untuk menghindari offset error)
                    $budgetData[$field] = $this->cleanNumeric($row[$csvIndex] ?? 0);
                }

                BudgetMaster::create($budgetData);

                // Tanda sukses (titik)
                $this->command->getOutput()->write('.'); 
            }
        }
        
        // Baris ini menangani semua kasus yang tidak secara eksplisit di-return di atas
        return compact('currentDepartemenId', 'currentProgramId', 'lastKodeAkunPrefix');
    }

    /**
     * Fungsi helper untuk membersihkan format angka dari pemisah ribuan (.), koma desimal (,),
     * dan karakter non-numerik lainnya, lalu mengembalikan float.
     * @param mixed $value Nilai yang akan dibersihkan.
     * @return float Nilai numerik.
     */
    private function cleanNumeric($value): float
    {
        if (is_null($value) || $value === '') return 0.0;
        
        // Trim spasi, tab, dan karakter non-cetak lainnya
        $value = trim($value); 
        
        // Hapus pemisah ribuan (titik)
        $value = str_replace('.', '', $value); 
        
        // Ubah koma desimal menjadi titik desimal
        $value = str_replace(',', '.', $value); 
        
        // Hapus semua karakter yang bukan angka, titik desimal, atau tanda minus
        $value = preg_replace('/[^0-9\.-]/', '', $value); 
        
        return (float) $value;
    }
}