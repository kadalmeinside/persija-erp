<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\ApprovalProcess;
use App\Models\SaldoCuti;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Get aggregated data for mobile dashboard (BFF Pattern).
     * This prevents the mobile app from making 4-5 different API calls on the Home Screen.
     */
    public function home(Request $request)
    {
        $user = $request->user();
        $karyawan = $user->karyawan;

        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        // 1. Get Today's Attendance Status
        $today = Carbon::today();
        $absensiHariIni = Absensi::where('id_karyawan', $karyawan->id)
            ->whereDate('tanggal', $today)
            ->first();

        // 2. Get Remaining Annual Leave (Cuti Tahunan)
        // Asumsi "Cuti Tahunan" selalu ada dan merupakan hak default
        $saldoCutiTahunan = SaldoCuti::where('id_karyawan', $karyawan->id)
            ->where('tahun_periode', date('Y'))
            ->whereHas('jenisCuti', function ($q) {
                $q->where('nama_cuti', 'like', '%Tahunan%');
            })
            ->first();

        // 3. (Optional) Check pending approvals if user is a manager
        // This is useful to show a red dot/badge on the dashboard
        $pendingApprovals = 0;
        if (in_array($user->roles->first()?->name, ['HR Manager', 'Direktur', 'Manajer Departemen'])) {
            $pendingApprovals = ApprovalProcess::where('id_karyawan_target', $karyawan->id)
                ->where('status', 'Pending')
                ->count();
        }

        return response()->json([
            'data' => [
                'user' => [
                    'name' => $karyawan->nama_lengkap,
                    'jabatan' => $karyawan->jabatan,
                    'foto_url' => $karyawan->foto_url,
                ],
                'attendance_today' => [
                    'status' => $absensiHariIni ? $absensiHariIni->status_kehadiran : 'Belum Absen',
                    'jam_masuk' => $absensiHariIni?->waktu_masuk,
                    'jam_keluar' => $absensiHariIni?->waktu_keluar,
                ],
                'leave_balance' => [
                    'annual_leave_remaining' => $saldoCutiTahunan ? $saldoCutiTahunan->saldo_akhir : 0,
                    'total_annual_leave' => $saldoCutiTahunan ? $saldoCutiTahunan->saldo_awal : 0,
                ],
                'tasks' => [
                    'pending_approvals' => $pendingApprovals
                ]
            ]
        ], 200);
    }
}
