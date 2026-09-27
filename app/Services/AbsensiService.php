<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\LokasiKantor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AbsensiService
{
    /**
     * Hitung jarak (meter) dari dua koordinat (Haversine Formula)
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // dalam meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * asin(sqrt($a));
        $dist = $earthRadius * $c;

        return $dist;
    }

    /**
     * Proses Clock In
     */
    public function processClockIn($karyawan, $latitude, $longitude, $fotoFile, $isDinasLuar, $catatan)
    {
        // --- Validasi Lokasi (Multi-Branch Geofencing) ---
        $lokasiTerdekat = null;
        if (!$isDinasLuar) {
            // Jika karyawan strict ke satu lokasi, ambil hanya lokasi tersebut
            if ($karyawan->is_strict_location && $karyawan->id_lokasi_kantor) {
                $lokasiList = LokasiKantor::where('id', $karyawan->id_lokasi_kantor)->where('is_active', true)->get();
                if ($lokasiList->isEmpty()) {
                    return ['success' => false, 'message' => 'Lokasi kantor penempatan Anda tidak aktif atau tidak ditemukan.'];
                }
            } else {
                $lokasiList = LokasiKantor::where('is_active', true)->get();
                if ($lokasiList->isEmpty()) {
                    return ['success' => false, 'message' => 'Lokasi kantor belum disetting HR.'];
                }
            }

            // Cari lokasi yang paling dekat & masuk dalam radius
            foreach ($lokasiList as $lokasi) {
                $distance = $this->calculateDistance($latitude, $longitude, $lokasi->latitude, $lokasi->longitude);
                if ($distance <= $lokasi->radius_meter) {
                    $lokasiTerdekat = $lokasi;
                    break;
                }
            }

            if (!$lokasiTerdekat) {
                // Hitung jarak ke lokasi terdekat untuk pesan error yang informatif
                $minDistance = PHP_INT_MAX;
                $namaLokasi = '-';
                foreach ($lokasiList as $lokasi) {
                    $d = $this->calculateDistance($latitude, $longitude, $lokasi->latitude, $lokasi->longitude);
                    if ($d < $minDistance) {
                        $minDistance = $d;
                        $namaLokasi = $lokasi->nama_kantor ?? 'kantor';
                    }
                }

                $errMsg = 'Anda berada di luar radius lokasi kantor. Lokasi terdekat: ' . $namaLokasi . ' (' . round($minDistance) . 'm).';
                if ($karyawan->is_strict_location) {
                    $errMsg = 'Anda diwajibkan absen HANYA di cabang ' . $namaLokasi . '. Anda berada di luar radius (' . round($minDistance) . 'm).';
                } else {
                    $errMsg .= ' Jika sedang bertugas di luar, gunakan fitur "Dinas Luar".';
                }

                return ['success' => false, 'message' => $errMsg];
            }
        }

        $today = Carbon::today()->toDateString();
        $absensi = Absensi::where('id_karyawan', $karyawan->id)->where('tanggal', $today)->first();

        if ($absensi && $absensi->waktu_masuk) {
            return ['success' => false, 'message' => 'Anda sudah absen masuk hari ini.'];
        }

        // --- Simpan Foto ---
        $fotoPath = $this->handlePhotoUpload($fotoFile, $karyawan->id);

        if (!$absensi) {
            $absensi = new Absensi();
            $absensi->id_karyawan = $karyawan->id;
            $absensi->tanggal = $today;
        }

        $absensi->waktu_masuk = now();
        $absensi->lat_masuk = $latitude;
        $absensi->lng_masuk = $longitude;
        $absensi->foto_masuk = $fotoPath;
        $absensi->status_kehadiran = 'Hadir';
        $absensi->is_dinas_luar = $isDinasLuar;
        $absensi->catatan = $catatan;
        $absensi->id_lokasi_kantor = $lokasiTerdekat?->id;
        $absensi->save();

        $msg = $isDinasLuar ? 'Berhasil Clock In (Dinas Luar).' : 'Berhasil Clock In.';
        return ['success' => true, 'message' => $msg, 'data' => $absensi];
    }

    /**
     * Proses Clock Out
     */
    public function processClockOut($karyawan, $latitude, $longitude, $fotoFile)
    {
        $today = Carbon::today()->toDateString();
        $absensi = Absensi::where('id_karyawan', $karyawan->id)->where('tanggal', $today)->first();

        if (!$absensi || !$absensi->waktu_masuk) {
            return ['success' => false, 'message' => 'Anda belum absen masuk hari ini.'];
        }
        if ($absensi->waktu_keluar) {
            return ['success' => false, 'message' => 'Anda sudah absen keluar hari ini.'];
        }

        // Validasi Lokasi Keluar (Opsional, saat ini sama dengan logika masuk jika dibutuhkan,
        // Tapi umumnya clock out hanya divalidasi biasa atau sama dengan clock in).
        // Karena di web sebelumnya clock out tidak divalidasi geofencing ketat (hanya simpan data), kita biarkan simpan.

        $fotoPath = $this->handlePhotoUpload($fotoFile, $karyawan->id);

        $absensi->waktu_keluar = now();
        $absensi->lat_keluar = $latitude;
        $absensi->lng_keluar = $longitude;
        $absensi->foto_keluar = $fotoPath;
        $absensi->save();

        return ['success' => true, 'message' => 'Berhasil Clock Out.', 'data' => $absensi];
    }

    /**
     * Handle photo upload both from Web (Base64) and Mobile (UploadedFile)
     */
    private function handlePhotoUpload($fotoFile, $karyawanId)
    {
        if (!$fotoFile) return null;

        // Jika dari Web (Base64 String)
        if (is_string($fotoFile) && preg_match('/^data:image\/(\w+);base64,/', $fotoFile)) {
            $data = substr($fotoFile, strpos($fotoFile, ',') + 1);
            $data = base64_decode($data);
            $fileName = 'absensi/' . $karyawanId . '_' . time() . '.jpg';
            Storage::disk('public')->put($fileName, $data);
            return $fileName;
        }

        // Jika dari Mobile API (Illuminate\Http\UploadedFile)
        if ($fotoFile instanceof \Illuminate\Http\UploadedFile) {
            return $fotoFile->store('absensi/' . date('Y/m'), 'public');
        }

        return null;
    }
}
