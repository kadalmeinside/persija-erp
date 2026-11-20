<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\KoreksiAbsensi;
use App\Models\Absensi;
use App\Models\LokasiKantor;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\AbsensiService;

class AttendanceController extends Controller
{
    /**
     * Tampilkan halaman Clock In / Clock Out untuk karyawan
     */
    public function clock()
    {
        $karyawan = Karyawan::with('biometric')->where('user_id', Auth::id())->first();
        if (!$karyawan) {
            abort(403, 'Profil Karyawan tidak ditemukan.');
        }

        if ($karyawan->biometric) {
            $karyawan->face_descriptor = $karyawan->biometric->getDescriptor('web');
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
    public function storeClock(Request $request, AbsensiService $service)
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

        if ($request->tipe === 'in') {
            $result = $service->processClockIn($karyawan, $request->latitude, $request->longitude, $request->foto, $isDinasLuar, $request->catatan);
        } else {
            $result = $service->processClockOut($karyawan, $request->latitude, $request->longitude, $request->foto);
        }

        return response()->json($result);
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

            $biometric = \App\Models\KaryawanBiometric::firstOrCreate(
                ['id_karyawan' => $karyawan->id]
            );
            $biometric->setDescriptor('web', $request->descriptor);

            return response()->json(['success' => true, 'message' => 'Wajah berhasil didaftarkan.']);
        }

        // View registrasi
        $karyawan = Karyawan::with('biometric')->where('user_id', Auth::id())->first();
        // Fallback for frontend that might still expect face_descriptor directly on karyawan
        if ($karyawan && $karyawan->biometric) {
            $karyawan->face_descriptor = $karyawan->biometric->getDescriptor('web');
        }

        return Inertia::render('Admin/Absensi/RegisterFace', [
            'karyawan' => $karyawan
        ]);
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
        $query = Absensi::with(['karyawan.departemen', 'lokasiKantor'])->where('tanggal', $date);

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
     * Halaman Rekap Absensi & Koreksi Absensi untuk HR
     */
    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request);
        $absensis = $query->orderBy('waktu_masuk', 'asc')->paginate(20)->withQueryString();

        $koreksiStatus = $request->input('koreksi_status');
        $koreksis = KoreksiAbsensi::with(['karyawan.departemen', 'approver', 'absensi'])
            ->when($koreksiStatus, fn($q) => $q->where('status', $koreksiStatus))
            ->latest()
            ->paginate(15, ['*'], 'koreksi_page')
            ->withQueryString();

        $koreksiPendingCount = KoreksiAbsensi::where('status', 'Pending')->count();

        return Inertia::render('Admin/Absensi/Index', [
            'absensis' => $absensis,
            'koreksis' => $koreksis,
            'koreksiPendingCount' => $koreksiPendingCount,
            'activeTab' => $request->input('tab', 'rekap'),
            'filters' => [
                'date' => $request->input('date', Carbon::today()->toDateString()),
                'status' => $request->input('status'),
                'time_in' => $request->input('time_in'),
                'time_out' => $request->input('time_out'),
                'koreksi_status' => $koreksiStatus,
            ]
        ]);
    }

    /**
     * Action approval/rejection untuk pengajuan koreksi absensi
     */
    public function koreksiAction(Request $request, KoreksiAbsensi $koreksi)
    {
        $user = Auth::user();
        if ($user?->karyawan && $koreksi->id_karyawan === $user->karyawan->id) {
            abort(403, 'Anda tidak dapat memproses koreksi absensi milik sendiri.');
        }

        if ($koreksi->status !== 'Pending') {
            return back()->with('error', 'Koreksi absensi sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'catatan' => 'nullable|string|max:500',
        ]);

        $isApprove = $validated['action'] === 'approve';

        \Illuminate\Support\Facades\DB::transaction(function () use ($koreksi, $validated, $isApprove) {
            $locked = KoreksiAbsensi::whereKey($koreksi->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'Pending') {
                throw new \RuntimeException('Koreksi absensi sudah diproses sebelumnya.');
            }

            if ($isApprove) {
                $absensi = Absensi::firstOrNew([
                    'id_karyawan' => $locked->id_karyawan,
                    'tanggal' => $locked->tanggal,
                ]);

                if ($locked->waktu_masuk_baru) {
                    $absensi->waktu_masuk = $locked->waktu_masuk_baru;
                }
                if ($locked->waktu_keluar_baru) {
                    $absensi->waktu_keluar = $locked->waktu_keluar_baru;
                }
                $absensi->status_kehadiran = 'Hadir';
                $absensi->catatan = ($absensi->catatan ? $absensi->catatan . ' | ' : '') . 'Koreksi: ' . $locked->alasan;
                $absensi->save();

                $locked->id_absensi = $absensi->id;
                $locked->status = 'Approved';
            } else {
                $locked->status = 'Rejected';
            }

            $locked->approved_by = Auth::id();
            $locked->approved_at = now();
            $locked->catatan_approver = $validated['catatan'] ?? null;
            $locked->save();
        });

        $koreksi->refresh();

        $targetUser = $koreksi->karyawan?->user;
        if ($targetUser) {
            $statusText = $isApprove ? 'Disetujui' : 'Ditolak';
            $targetUser->notify(new \App\Notifications\EssSubmissionNotification(
                'attendance',
                "Pengajuan Koreksi Absensi {$statusText}",
                "Pengajuan koreksi absensi tanggal " . \Carbon\Carbon::parse($koreksi->tanggal)->format('d/m/Y') . " telah {$statusText}." . (!empty($validated['catatan']) ? " Catatan: {$validated['catatan']}" : ''),
                '/ess?tab=koreksi'
            ));
        }

        return back()->with('success', "Pengajuan koreksi absensi berhasil " . ($isApprove ? 'disetujui.' : 'ditolak.'));
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
