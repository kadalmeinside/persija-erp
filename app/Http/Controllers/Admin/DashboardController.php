<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Karyawan;
use App\Models\ApprovalProcess;
use App\Models\User;
use App\Models\PengajuanCuti;
use App\Models\PengajuanHeader;
use App\Models\InvoiceHeader;
use App\Models\SaldoCuti;
use App\Enums\Role;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $data = [
            'role' => $user->getRoleNames()->first(),
            'stats' => [],
            'charts' => [],
            'activities' => []
        ];

        // 1. SUPER ADMIN / DIRECTOR / GENERAL VIEW
        if ($user->hasRole(Role::adminRoles())) {
            $data['stats'] = [
                'total_employees' => Karyawan::where('status_karyawan', '!=', 'Resign')->count(),
                'pending_approvals' => ApprovalProcess::where('status', 'Pending')->count(),
                'total_users' => User::count(),
            ];
            
            // Simple Cash Flow (Dummy for now, or real if tables populated)
            // $data['charts']['cash_flow'] = ... 
        }

        // 2. HR VIEW
        if ($user->hasRole(Role::hrRoles())) {
            $today = Carbon::today();
            $data['hr_stats'] = [
                'pending_leave_requests' => PengajuanCuti::where('status', 'Pending')->count(),
                'employees_on_leave' => PengajuanCuti::where('status', 'Approved')
                                            ->whereDate('tgl_mulai', '<=', $today)
                                            ->whereDate('tgl_selesai', '>=', $today)
                                            ->count(),
                'new_hires_month' => Karyawan::whereMonth('tgl_bergabung', $today->month)
                                        ->whereYear('tgl_bergabung', $today->year)
                                        ->count(),
            ];
        }

        // 3. FINANCE VIEW
        if ($user->hasRole(Role::financeRoles())) {
            $data['finance_stats'] = [
                'pending_expenses' => PengajuanHeader::where('status_global', 'Pending Approval')->count(),
                'unpaid_invoices' => InvoiceHeader::where('status', 'Unpaid')->count(),
                'need_settlement' => PengajuanHeader::where('tipe_pengajuan', 'UangMuka')
                                        ->where('status_global', 'Paid') // Sudah bayar tapi belum settlement
                                        ->count(),
            ];
        }

        // 4. EMPLOYEE / PERSONAL VIEW (ALL USERS)
        $karyawan = $user->karyawan;
        if ($karyawan) {
            $currentYear = date('Y');
            $data['my_stats'] = [
                'leave_balance' => SaldoCuti::where('id_karyawan', $karyawan->id)
                                    ->where('tahun_periode', $currentYear)
                                    ->whereHas('jenisCuti', function($q) {
                                        $q->where('nama_cuti', 'like', '%Tahunan%');
                                    })
                                    ->value('saldo_akhir') ?? 0,
                'my_pending_requests' => PengajuanHeader::where('id_pengaju', $user->id)
                                            ->where('status_global', 'Pending Approval')
                                            ->count() 
                                            + 
                                         PengajuanCuti::where('id_karyawan', $karyawan->id)
                                            ->where('status', 'Pending')
                                            ->count(),
            ];
        }

        // 5. ACTIONABLE COUNTS (For Top Section)
        $data['action_counts'] = [];

        // Logic for Leave Approvals Count
        // Bugfix: Logic for Leave Approvals Count should be strict (Only if I am the approver)
        // Logic for Leave Approvals Count
        if ($karyawan) {
            $leaveCount = PengajuanCuti::whereHas('approvalProcess', function($q) use ($karyawan) {
                $q->where('id_karyawan_target', $karyawan->id)
                  ->where('status', 'Pending');
            })->count();

            // Only show if there are pending items OR user is a likely approver (Manager/HR)
            if ($leaveCount > 0 || $user->hasRole([Role::SUPER_ADMIN->value, Role::MANAJER_DEPARTEMEN->value, Role::HR_MANAGER->value, Role::HR_STAFF->value, Role::DIREKTUR->value])) {
                 $data['action_counts']['leave_approvals'] = $leaveCount;
            }
        }

        if ($karyawan) {
            // FIX: Count PengajuanHeader directly to avoid counting orphan ApprovalProcess records (where header is deleted)
             $expenseCount = PengajuanHeader::whereHas('approvalProcess', function($q) use ($karyawan) {
                                        $q->where('id_karyawan_target', $karyawan->id)
                                          ->where('status', 'Pending');
                                    })->count();
            
            // Only show widget if I have tasks OR I am a functional Finance/Manager role (who wants to see "0")
            // Removed 'Super Admin' from this list so they don't see empty widgets
            if ($expenseCount > 0 || $user->hasRole([Role::FINANCE_MANAGER->value, Role::STAF_FINANCE->value, Role::MANAJER_DEPARTEMEN->value, Role::DIREKTUR->value])) {
                $data['action_counts']['expense_approvals'] = $expenseCount;
            }
        }

        // 6. MY ACTIVE TASKS
        if ($karyawan) {
            $data['my_active_tasks'] = \App\Models\Task::with(['departemen'])
                ->where('id_karyawan_assignee', $karyawan->id)
                ->whereIn('status', ['Todo', 'In Progress', 'In Review'])
                ->orderBy('due_date', 'asc')
                ->take(5)
                ->get();
                
            $data['action_counts']['active_tasks_count'] = \App\Models\Task::where('id_karyawan_assignee', $karyawan->id)
                ->whereIn('status', ['Todo', 'In Progress', 'In Review'])
                ->count();
                
            $data['action_counts']['overdue_tasks_count'] = \App\Models\Task::where('id_karyawan_assignee', $karyawan->id)
                ->whereIn('status', ['Todo', 'In Progress', 'In Review'])
                ->where('due_date', '<', \Carbon\Carbon::today())
                ->count();
        }

        return Inertia::render('Admin/Dashboard', [
            'dashboardData' => $data
        ]);
    }
}

