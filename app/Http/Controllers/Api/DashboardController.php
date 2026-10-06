<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\ApprovalProcess;
use App\Models\SaldoCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\Task;
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

        // Pending approval is assigned to an employee, not inferred from role.
        $roles = $user->getRoleNames();
        $pendingApprovals = ApprovalProcess::where('id_karyawan_target', $karyawan->id)
            ->where('status', 'Pending')
            ->count();

        // Check if employee is an approver (in rules or has any process targeted to them)
        $isApprover = \App\Models\ApprovalRule::where('id_karyawan_approver', $karyawan->id)->exists()
            || ApprovalProcess::where('id_karyawan_target', $karyawan->id)->exists();

        $data = [
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
                'pending_approvals' => $pendingApprovals,
                'is_approver' => $isApprover,
            ],
        ];

        if ($roles->contains('Direktur')) {
            $today = Carbon::today();
            $todayTasks = Task::with('assignee:id,nama_lengkap')
                ->activeKanban()
                ->whereDate('due_date', $today)
                ->orderBy('priority')
                ->get(['id', 'title', 'status', 'priority', 'due_date', 'id_karyawan_assignee']);
            $overdueTasks = Task::with('assignee:id,nama_lengkap')
                ->activeKanban()
                ->whereDate('due_date', '<', $today)
                ->where('status', '!=', 'Done')
                ->orderBy('due_date')
                ->get(['id', 'title', 'status', 'priority', 'due_date', 'id_karyawan_assignee']);
            $onLeave = PengajuanCuti::with('karyawan:id,nama_lengkap')
                ->where('status', 'Approved')
                ->whereDate('tgl_mulai', '<=', $today)
                ->whereDate('tgl_selesai', '>=', $today)
                ->get(['id', 'id_karyawan', 'tgl_mulai', 'tgl_selesai', 'id_jenis_cuti']);
            $presentIds = Absensi::whereDate('tanggal', $today)->pluck('id_karyawan');
            $absent = Karyawan::whereNotIn('id', $presentIds)
                ->whereNotIn('status_karyawan', ['Resign', 'Nonaktif', 'Terminated'])
                ->orderBy('nama_lengkap')
                ->get(['id', 'nama_lengkap', 'jabatan', 'id_departemen']);

            $data['director_overview'] = [
                'today_tasks' => $todayTasks,
                'overdue_tasks' => $overdueTasks,
                'on_leave_today' => $onLeave->map(fn ($leave) => [
                    'id' => $leave->id,
                    'employee_name' => $leave->karyawan?->nama_lengkap,
                    'start_date' => $leave->tgl_mulai,
                    'end_date' => $leave->tgl_selesai,
                ])->values(),
                'absent_today' => $absent,
            ];
        }

        return response()->json([
            'data' => $data,
        ], 200);
    }
}
