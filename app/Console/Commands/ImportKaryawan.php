<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\RiwayatKarir;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportKaryawan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:karyawan {file : Path ke file CSV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data karyawan dari CSV ke Database untuk Production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan di path: {$filePath}");
            return;
        }

        $this->info("Membaca file CSV...");
        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Skip header

        DB::beginTransaction();
        try {
            $count = 0;
            while (($row = fgetcsv($file)) !== false) {
                // Skip empty row or invalid row
                if (empty($row[2]) || $row[2] == 'Nama' || $row[2] == 'KELUAR' || $row[2] == 'KARYAWAN LAMA') {
                    continue;
                }
                
                $noKontrak = $row[1] ?? '';
                $nama = trim($row[2]);
                $nik = trim($row[3]);
                $jenisKelaminText = trim($row[4]);
                $ttl = trim($row[5]);
                
                // Address logic based on index
                $alamatArr = [];
                for($i=6; $i<=9; $i++) {
                    if(!empty(trim($row[$i] ?? ''))) {
                        $alamatArr[] = trim($row[$i]);
                    }
                }
                $alamatText = implode(', ', $alamatArr);
                
                $divisi = trim($row[10] ?? '');
                $jabatan = trim($row[11] ?? '');
                $mulaiKontrakText = trim($row[13] ?? '');
                $akhirKontrakText = trim($row[14] ?? '');
                $gajiText = trim($row[15] ?? '');

                // 1. Parse Jenis Kelamin
                $jenisKelamin = (strtolower($jenisKelaminText) === 'perempuan') ? 'P' : 'L';

                // 2. Parse TTL
                $tempatLahir = null;
                $tglLahir = null;
                if (strpos($ttl, ',') !== false) {
                    $ttlParts = explode(',', $ttl);
                    $tempatLahir = trim($ttlParts[0]);
                    $tglLahir = $this->parseIndonesianDate(trim($ttlParts[1] ?? ''));
                }

                // 3. Parse Tanggal Gabung & Akhir
                $tglBergabung = $this->parseIndonesianDate($mulaiKontrakText);
                $tglBerakhir = $this->parseIndonesianDate($akhirKontrakText);

                // 4. Parse Gaji
                $gaji = (int) preg_replace('/[^0-9]/', '', $gajiText);

                // 5. Mapping Divisi ke id_departemen
                $idDepartemen = $this->mapDepartemen($divisi);

                $this->line("Mengimport: {$nama} | NIK: {$nik} | Div: {$divisi}");

                // 6. Cek jika NIK sudah ada
                if (Karyawan::where('nomor_induk_karyawan', $nik)->exists()) {
                    $this->warn("   -> Karyawan dengan NIK {$nik} sudah ada, di-skip.");
                    continue;
                }

                // 7. Buat User Login
                $cleanNik = preg_replace('/[^a-zA-Z0-9]/', '', $nik);
                $email = strtolower($cleanNik) . '@persijadevelopment.id';
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $nama,
                        'password' => Hash::make('persija123'),
                        'pin' => Hash::make('123456'), // PIN Default
                    ]
                );

                // Assign Role 'User'
                if (method_exists($user, 'hasRole') && !$user->hasRole('User')) {
                    $user->assignRole('User');
                }

                // 8. Buat Karyawan
                $karyawan = Karyawan::create([
                    'user_id' => $user->id,
                    'id_departemen' => $idDepartemen,
                    'nomor_induk_karyawan' => $nik,
                    'nama_lengkap' => $nama,
                    'jabatan' => $jabatan,
                    'gaji_pokok' => $gaji,
                    'jenis_kelamin' => $jenisKelamin,
                    'tgl_bergabung' => $tglBergabung,
                    'tempat_lahir' => $tempatLahir,
                    'tgl_lahir' => $tglLahir,
                    'alamat' => $alamatText,
                    'status_karyawan' => 'Kontrak',
                    'status_ptkp' => 'TK/0',
                    'is_strict_location' => false,
                ]);

                // 9. Buat Riwayat Karir
                RiwayatKarir::create([
                    'id_karyawan' => $karyawan->id,
                    'id_departemen' => $idDepartemen,
                    'jabatan' => $jabatan,
                    'status_karyawan' => 'Kontrak',
                    'tanggal_efektif' => $tglBergabung ?? now(),
                    'tanggal_berakhir_kontrak' => $tglBerakhir,
                    'gaji_pokok' => $gaji,
                ]);

                $count++;
            }
            fclose($file);

            DB::commit();
            $this->info("Import selesai! Berhasil mengimport {$count} Karyawan.");

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($file);
            $this->error("Terjadi kesalahan: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }

    private function mapDepartemen($divisi)
    {
        $divisi = strtolower(trim($divisi));
        if (str_contains($divisi, 'fa') || str_contains($divisi, 'finance')) return 1;
        if (str_contains($divisi, 'development')) return 2;
        if (str_contains($divisi, 'ga') || str_contains($divisi, 'general affair')) return 3;
        if ($divisi == 'it') return 4;
        if (str_contains($divisi, 'it & media')) return 4; 
        if (str_contains($divisi, 'media')) return 5;
        if (str_contains($divisi, 'venue')) return 6;
        if (str_contains($divisi, 'direktur')) return 7;

        return 3; // Default ke GA jika tidak dikenali
    }

    private function parseIndonesianDate($dateStr)
    {
        if (empty($dateStr) || $dateStr == '-') return null;

        if (str_contains($dateStr, ',')) {
            $parts = explode(',', $dateStr);
            $dateStr = trim($parts[1] ?? $dateStr);
        }

        $bulan_indo = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
            'January', 'February', 'March', 'April', 'May', 'June', 'Juny',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        $bulan_en = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
            'January', 'February', 'March', 'April', 'May', 'June', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        $dateEn = str_replace($bulan_indo, $bulan_en, $dateStr);

        try {
            return Carbon::parse($dateEn)->format('Y-m-d');
        } catch (\Exception $e) {
            return null; // Ignore if weird format
        }
    }
}
