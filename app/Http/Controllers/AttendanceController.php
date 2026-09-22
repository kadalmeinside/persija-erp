<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Absensi;
use App\Models\LokasiKantor;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    /**
     * Tampilkan halaman Clock In / Clock Out untuk karyawan
     */
    public function clock()
    {
        $karyawan = Karyawan::where('user_id', Auth::id())->first();
        if (!$karyawan) {
            abort(403, 'Profil Karyawan tidak ditemukan.');
        }

        $today = Carbon::today()->toDateString();
        $absensiHariIni = Absensi::where('id_karyawan', $karyawan->id)
                                 ->where('tanggal', $today)
                                 ->first();

        // Ambil lokasi kantor pusat sebagai referensi (atau lokasi khusus karyawan jika ada)
        $lokasiKantor = LokasiKantor::where('is_active', true)->first();

        return Inertia::render('Admin/Absensi/Clock', [
            'karyawan' => $karyawan,
            'absensi' => $absensiHariIni,
            'lokasiKantor' => $lokasiKantor
        ]);
    }

    /**
     * Proses penyimpanan absen masuk/keluar
     */
    public function storeClock(Request $request)
    {
        $request->validate([
            'tipe' => 'required|in:in,out',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto' => 'required|string', // base64 string
        ]);

        $karyawan = Karyawan::where('user_id', Auth::id())->first();
        if (!$karyawan) {
            return response()->json(['success' => false, 'message' => 'Profil Karyawan tidak ditemukan.']);
        }

        // Validasi Lokasi (Haversine Formula)
        $lokasiKantor = LokasiKantor::where('is_active', true)->first();
        if (!$lokasiKantor) {
            return response()->json(['success' => false, 'message' => 'Lokasi kantor belum disetting HR.']);
        }

        $distance = $this->calculateDistance($request->latitude, $request->longitude, $lokasiKantor->latitude, $lokasiKantor->longitude);
        if ($distance > $lokasiKantor->radius_meter) {
            return response()->json([
                'success' => false, 
                'message' => 'Anda berada di luar radius kantor (' . round($distance) . ' meter dari ' . $lokasiKantor->radius_meter . 'm yang diizinkan).'
            ]);
        }

        // Simpan Foto
        $fotoPath = null;
        if (preg_match('/^data:image\/(\w+);base64,/', $request->foto)) {
            $data = substr($request->foto, strpos($request->foto, ',') + 1);
            $data = base64_decode($data);
            $fileName = 'absensi/' . $karyawan->id . '_' . time() . '.jpg';
            Storage::disk('public')->put($fileName, $data);
            $fotoPath = $fileName;
        }

        $today = Carbon::today()->toDateString();
        $absensi = Absensi::where('id_karyawan', $karyawan->id)
                          ->where('tanggal', $today)
                          ->first();

        $now = Carbon::now();

        if ($request->tipe === 'in') {
            if ($absensi && $absensi->waktu_masuk) {
                return response()->json(['success' => false, 'message' => 'Anda sudah absen masuk hari ini.']);
            }
            if (!$absensi) {
                $absensi = new Absensi();
                $absensi->id_karyawan = $karyawan->id;
                $absensi->tanggal = $today;
            }
            $absensi->waktu_masuk = $now;
            $absensi->lat_masuk = $request->latitude;
            $absensi->lng_masuk = $request->longitude;
            $absensi->foto_masuk = $fotoPath;
            $absensi->status_kehadiran = 'Hadir'; // Boleh dikembangkan cek jam keterlambatan
            $absensi->save();

            return response()->json(['success' => true, 'message' => 'Berhasil Clock In.']);
        } 
        else if ($request->tipe === 'out') {
            if (!$absensi || !$absensi->waktu_masuk) {
                return response()->json(['success' => false, 'message' => 'Anda belum absen masuk hari ini.']);
            }
            if ($absensi->waktu_keluar) {
                return response()->json(['success' => false, 'message' => 'Anda sudah absen keluar hari ini.']);
            }

            $absensi->waktu_keluar = $now;
            $absensi->lat_keluar = $request->latitude;
            $absensi->lng_keluar = $request->longitude;
            $absensi->foto_keluar = $fotoPath;
            $absensi->save();

            return response()->json(['success' => true, 'message' => 'Berhasil Clock Out.']);
        }
    }

    /**
     * Mendaftarkan Data Wajah (Face Descriptor)
     */
    public function registerFace(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'descriptor' => 'required|string'
            ]);

            $karyawan = Karyawan::where('user_id', Auth::id())->first();
            if (!$karyawan) {
                return response()->json(['success' => false, 'message' => 'Karyawan tidak ditemukan']);
            }

            $karyawan->face_descriptor = $request->descriptor;
            $karyawan->save();

            return response()->json(['success' => true, 'message' => 'Wajah berhasil didaftarkan.']);
        }

        // View registrasi
        $karyawan = Karyawan::where('user_id', Auth::id())->first();
        return Inertia::render('Admin/Absensi/RegisterFace', [
            'karyawan' => $karyawan
        ]);
    }

    /**
     * Hitung jarak (meter) dari dua koordinat (Haversine Formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // dalam meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $dist = $earthRadius * $c;

        return $dist;
    }

    /**
     * Halaman Rekap Absensi untuk HR
     */
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $status = $request->input('status');
        $timeIn = $request->input('time_in');
        $timeOut = $request->input('time_out');

        $query = Absensi::with('karyawan')->where('tanggal', $date);

        if ($status) {
            $query->where('status_kehadiran', $status);
        }

        if ($timeIn) {
            // For example, filter check-ins that happened ON or AFTER the specified time
            $query->whereTime('waktu_masuk', '>=', $timeIn);
        }

        if ($timeOut) {
            // For example, filter check-outs that happened ON or BEFORE the specified time
            $query->whereTime('waktu_keluar', '<=', $timeOut);
        }

        $absensis = $query->orderBy('waktu_masuk', 'asc')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Absensi/Index', [
            'absensis' => $absensis,
            'filters' => [
                'date' => $date,
                'status' => $status,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
            ]
        ]);
    }

    /**
     * Halaman Riwayat Absensi Pribadi
     */
    public function myAttendance()
    {
        $karyawan = Karyawan::where('user_id', Auth::id())->first();
        $absensis = Absensi::where('id_karyawan', $karyawan?->id)->orderBy('tanggal', 'desc')->get();

        return Inertia::render('Admin/Absensi/MyAttendance', [
            'absensis' => $absensis
        ]);
    }
}
