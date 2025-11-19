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
        // PERBAIKAN 1: Sesuaikan nama file dengan yang Anda upload
        $filePath = "seeders/anggaran.csv"; // Path di dalam storage/app/
        $tahunAnggaran = 2025; // Sesuaikan jika perlu

        if (!Storage::disk('local')->exists($filePath)) {
            $this->command->error("File anggaran tidak ditemukan di: storage/app/{$filePath}");
            return;
        }

        $this->command->info("Memulai parsing file anggaran: {$filePath}...");

        DB::transaction(function () use ($filePath, $tahunAnggaran) {
            
            // Inisialisasi variabel status
            $currentDepartemenId = null;
            $currentProgramId = null;
            $currentTipeAkun = null; // 'Pendapatan' atau 'Biaya'
            $skipRows = 5; // PERBAIKAN: File baru Anda memiliki 5 baris header

            $file = Storage::disk('local')->readStream($filePath);

            // PERBAIKAN 2: Tambahkan parameter pemisah ';'
            while (($row = fgetcsv($file, 0, ';')) !== false) {
                
                // Lewati X baris header pertama
                if ($skipRows > 0) {
                    $skipRows--;
                    continue;
                }

                // Cek jika baris kosong (sering terjadi pada file CSV)
                if (empty(implode('', $row)) || count($row) < 3) {
                    continue;
                }

                $kodeAkun = trim($row[0] ?? '');
                $uraian = trim($row[1] ?? '');
                $anggaranTotal = $this->cleanNumeric($row[2] ?? 0);

                // 1. Cek Header Tipe Akun (PENDAPATAN / BIAYA)
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
                
                // Lewati baris header tabel (Kode Akun, Uraian, Anggaran...)
                if (strtoupper($kodeAkun) == 'KODE AKUN') {
                    continue;
                }

                // 2. Cek Header Departemen (e.g., "5-01. BOARDING SCHOOL")
                // Asumsi: Header Departemen memiliki 'Anggaran' (kolom 2) kosong atau 0
                if ((preg_match('/^[45]-\d{2}\./', $kodeAkun) || !empty($uraian)) && $anggaranTotal == 0) {
                    
                    // Cek jika ini adalah header Program (sub-header di bawah Departemen, cth: "Gaji")
                    // Asumsi: Header Program tidak memiliki Kode Akun
                    if (empty($kodeAkun) && !empty($uraian)) {
                        $program = ProgramKerja::firstOrCreate(['nama_program' => $uraian]);
                        $currentProgramId = $program->id;
                        $this->command->getOutput()->writeln("<comment>  -> Mengganti Program ke: {$uraian}</comment>");
                    }
                    // Cek jika ini adalah header Departemen
                    else {
                        $namaDept = !empty($kodeAkun) ? $kodeAkun : $uraian;
                        $dept = Departemen::firstOrCreate(['nama_departemen' => $namaDept]);
                        $currentDepartemenId = $dept->id;
                        $currentProgramId = null; // Reset program setiap ganti departemen
                        $this->command->info("Mengganti Departemen ke: {$namaDept}");
                    }
                    continue;
                }

                // 3. Proses Baris Data (Anggaran Aktual)
                // Asumsi: Baris data adalah yang memiliki Kode Akun dan kombinasi Dept+Program
                if (preg_match('/^[45]-\d{4}-\d{2}$/', $kodeAkun) && $currentDepartemenId && $currentProgramId) {

                    // Tentukan Tipe Akun
                    $tipeAkun = 'Biaya'; // Default
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
     * PERBAIKAN 3: Membersihkan string numerik (menghapus spasi dan titik).
     */
    private function cleanNumeric($value): float
    {
        if (is_null($value) || $value === '') {
            return 0;
        }
        $value = trim($value); // Hapus spasi di awal/akhir
        $value = str_replace('.', '', $value); // Hapus titik ribuan
        $value = str_replace(',', '.', $value); // Ganti koma desimal (jika ada)
        
        // Hapus karakter non-numerik lainnya (kecuali minus dan titik desimal)
        $value = preg_replace('/[^0-9\.-]/', '', $value); 

        return (float) $value;
    }
}