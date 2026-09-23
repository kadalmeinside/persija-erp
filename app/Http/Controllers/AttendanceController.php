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
        $isDinasLuar = filter_var($request->input('is_dinas_luar', false), FILTER_VALIDATE_BOOLEAN);

        $request->validate([
            'tipe'          => 'required|in:in,out',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
            'foto'          => 'required|string', // base64 string
            'catatan'       => $isDinasLuar ? 'required|string|min:5' : 'nullable|string',
        ]);

        $karyawan = Karyawan::where('user_id', Auth::id())->first();
        if (!$karyawan) {
            return response()->json(['success' => false, 'message' => 'Profil Karyawan tidak ditemukan.']);
        }

        // --- Validasi Lokasi (Multi-Branch Geofencing) ---
        $lokasiTerdekat = null;
        if (!$isDinasLuar) {
            // Jika karyawan strict ke satu lokasi, ambil hanya lokasi tersebut
            if ($karyawan->is_strict_location && $karyawan->id_lokasi_kantor) {
                $lokasiList = LokasiKantor::where('id', $karyawan->id_lokasi_kantor)->where('is_active', true)->get();
                if ($lokasiList->isEmpty()) {
                    return response()->json(['success' => false, 'message' => 'Lokasi kantor penempatan Anda tidak aktif atau tidak ditemukan.']);
                }
            } else {
                $lokasiList = LokasiKantor::where('is_active', true)->get();
                if ($lokasiList->isEmpty()) {
                    return response()->json(['success' => false, 'message' => 'Lokasi kantor belum disetting HR.']);
                }
            }

            // Cari lokasi yang paling dekat & masuk dalam radius
            foreach ($lokasiList as $lokasi) {
                $distance = $this->calculateDistance(
                    $request->latitude, $request->longitude,
                    $lokasi->latitude, $lokasi->longitude
                );
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
                    $d = $this->calculateDistance($request->latitude, $request->longitude, $lokasi->latitude, $lokasi->longitude);
                    if ($d < $minDistance) {
                        $minDistance = $d;
                        $namaLokasi = $lokasi->nama ?? 'kantor';
                    }
                }
                
                $errMsg = 'Anda berada di luar radius lokasi kantor. Lokasi terdekat: ' . $namaLokasi . ' (' . round($minDistance) . 'm).';
                if ($karyawan->is_strict_location) {
                    $errMsg = 'Anda diwajibkan absen HANYA di cabang ' . $namaLokasi . '. Anda berada di luar radius (' . round($minDistance) . 'm).';
                } else {
                    $errMsg .= ' Jika sedang bertugas di luar, gunakan fitur "Dinas Luar".';
                }

                return response()->json([
                    'success' => false,
                    'message' => $errMsg,
                ]);
            }
        }

        // --- Simpan Foto ---
        $fotoPath = null;
        if (preg_match('/^data:image\/(\w+);base64,/', $request->foto)) {
            $data     = substr($request->foto, strpos($request->foto, ',') + 1);
            $data     = base64_decode($data);
            $fileName = 'absensi/' . $karyawan->id . '_' . time() . '.jpg';
            Storage::disk('public')->put($fileName, $data);
            $fotoPath = $fileName;
        }

        $today   = Carbon::today()->toDateString();
        $absensi = Absensi::where('id_karyawan', $karyawan->id)->where('tanggal', $today)->first();
        $now     = Carbon::now();

        if ($request->tipe === 'in') {
            if ($absensi && $absensi->waktu_masuk) {
                return response()->json(['success' => false, 'message' => 'Anda sudah absen masuk hari ini.']);
            }
            if (!$absensi) {
                $absensi              = new Absensi();
                $absensi->id_karyawan = $karyawan->id;
                $absensi->tanggal     = $today;
            }
            $absensi->waktu_masuk      = $now;
            $absensi->lat_masuk        = $request->latitude;
            $absensi->lng_masuk        = $request->longitude;
            $absensi->foto_masuk       = $fotoPath;
            $absensi->status_kehadiran = 'Hadir';
            $absensi->is_dinas_luar    = $isDinasLuar;
            $absensi->catatan          = $request->catatan;
            $absensi->id_lokasi_kantor = $lokasiTerdekat?->id;
            $absensi->save();

            $msg = $isDinasLuar ? 'Berhasil Clock In (Dinas Luar).' : 'Berhasil Clock In.';
            return response()->json(['success' => true, 'message' => $msg]);
        } elseif ($request->tipe === 'out') {
            if (!$absensi || !$absensi->waktu_masuk) {
                return response()->json(['success' => false, 'message' => 'Anda belum absen masuk hari ini.']);
            }
            if ($absensi->waktu_keluar) {
                return response()->json(['success' => false, 'message' => 'Anda sudah absen keluar hari ini.']);
            }

            $absensi->waktu_keluar = $now;
            $absensi->lat_keluar   = $request->latitude;
            $absensi->lng_keluar   = $request->longitude;
            $absensi->foto_keluar  = $fotoPath;
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
    private function buildFilteredQuery(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $status = $request->input('status');
        $timeIn = $request->input('time_in');
        $timeOut = $request->input('time_out');
        $query = Absensi::with(['karyawan', 'lokasiKantor'])->where('tanggal', $date);

        if ($status) {
            $query->where('status_kehadiran', $status);
        }

        if ($timeIn) {
            $query->whereTime('waktu_masuk', '>=', $timeIn);
        }

        if ($timeOut) {
            $query->whereTime('waktu_keluar', '<=', $timeOut);
        }

        return $query;
    }

    /**
     * Halaman Rekap Absensi untuk HR
     */
    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request);
        
        $absensis = $query->orderBy('waktu_masuk', 'asc')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Absensi/Index', [
            'absensis' => $absensis,
            'filters' => [
                'date' => $request->input('date', Carbon::today()->toDateString()),
                'status' => $request->input('status'),
                'time_in' => $request->input('time_in'),
                'time_out' => $request->input('time_out'),
            ]
        ]);
    }

    /**
     * Print Rekap Absensi
     */
    public function print(Request $request)
    {
        $query = $this->buildFilteredQuery($request);
        $absensis = $query->orderBy('waktu_masuk', 'asc')->get();
        
        return view('admin.absensi.print', [
            'absensis' => $absensis,
            'date' => $request->input('date', Carbon::today()->toDateString())
        ]);
    }

    /**
     * Export PDF Rekap Absensi
     */
    public function exportPdf(Request $request)
    {
        $query = $this->buildFilteredQuery($request);
        $absensis = $query->orderBy('waktu_masuk', 'asc')->get();
        $date = $request->input('date', Carbon::today()->toDateString());
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.absensi.print', [
            'absensis' => $absensis,
            'date' => $date
        ])->setPaper('a4', 'landscape');

        return $pdf->download("Rekap_Absensi_{$date}.pdf");
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
