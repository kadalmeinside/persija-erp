<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\HariLibur;
use App\Models\PengajuanCuti;
use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', date('n'));
        $year = $request->input('year', date('Y'));

        // 1. Fetch Holidays
        $holidays = HariLibur::whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->get()
            ->toBase()
            ->map(function ($holiday) {
                return [
                    'id' => 'holiday-' . $holiday->id,
                    'title' => $holiday->keterangan,
                    'start' => $holiday->tanggal->format('Y-m-d'),
                    'type' => 'holiday',
                    'color' => 'red'
                ];
            });

        // 2. Fetch Approved Leave
        $user = Auth::user();
        $query = PengajuanCuti::with(['karyawan', 'jenisCuti'])
            ->where('status', 'Approved')
            ->where(function ($q) use ($year, $month) {
                // Filter overlapping dates with selected month
                $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
                $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
                
                $q->whereBetween('tgl_mulai', [$startOfMonth, $endOfMonth])
                  ->orWhereBetween('tgl_selesai', [$startOfMonth, $endOfMonth])
                  ->orWhere(function ($sub) use ($startOfMonth, $endOfMonth) {
                      $sub->where('tgl_mulai', '<=', $startOfMonth)
                          ->where('tgl_selesai', '>=', $endOfMonth);
                  });
            });

        // Role-based filtering
        if (!$user->hasRole([Role::SUPER_ADMIN->value, Role::HR_MANAGER->value, Role::DIREKTUR->value, Role::MANAJER_DEPARTEMEN->value])) {
            // Staff only sees own leave
            if ($user->karyawan) {
                $query->where('id_karyawan', $user->karyawan->id);
            } else {
                // Non-karyawan user (shouldn't happen usually)
                $query->whereRaw('0 = 1'); 
            }
        } elseif ($user->hasRole(Role::MANAJER_DEPARTEMEN->value) && $user->karyawan) {
             // Manager sees own department
             $query->whereHas('karyawan', function($q) use ($user) {
                 $q->where('id_departemen', $user->karyawan->id_departemen);
             });
        }

        $leaves = $query->get()->toBase()->map(function ($leave) use ($user) {
            $isOwn = $user->karyawan && $user->karyawan->id === $leave->id_karyawan;
            return [
                'id' => 'leave-' . $leave->id,
                'title' => $leave->karyawan->nama_lengkap . ' (' . $leave->jenisCuti->nama_cuti . ')',
                'start' => $leave->tgl_mulai,
                'end' => $leave->tgl_selesai, // FullCalendar expects end date exclusive usually, but for custom we handle it
                'type' => 'leave',
                'color' => $isOwn ? 'green' : 'blue',
                'is_own' => $isOwn,
                'details' => $leave->alasan
            ];
        });

        // 3. Fetch Company Events (Internal Agenda)
        $companyEvents = \App\Models\CompanyEvent::whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->get()
            ->toBase()
            ->map(function ($event) {
                return [
                    'id' => 'event-' . $event->id,
                    'real_id' => $event->id, // For edit/delete
                    'title' => $event->title,
                    'start' => $event->start_date->format('Y-m-d H:i:s'), // Full datetime for events
                    'end' => $event->end_date ? $event->end_date->format('Y-m-d H:i:s') : null,
                    'type' => 'event',
                    'color' => $event->color, // purple
                    'description' => $event->description,
                    'location' => $event->location,
                    'is_managed' => true // Flag for UI to allow edit if admin
                ];
            });

        // 4. Fetch Tasks (Based on Privacy Rules)
        $karyawan = $user->karyawan;
        $tasksQuery = \App\Models\Task::with(['assignee', 'departemen'])
            ->whereNotNull('due_date')
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $month);

        $isAdmin = $user->hasRole(['super-admin', 'admin', 'hr']);
        if (!$isAdmin && $karyawan) {
            $isHead = \App\Models\Departemen::where('id_karyawan_kepala', $karyawan->id)->exists();
            if ($isHead) {
                $departemenIds = \App\Models\Departemen::where('id_karyawan_kepala', $karyawan->id)->pluck('id')->toArray();
                $karyawanIds = \App\Models\Karyawan::whereIn('id_departemen', $departemenIds)->pluck('id')->toArray();
                $karyawanIds[] = $karyawan->id;

                $tasksQuery->where(function ($q) use ($karyawanIds, $karyawan) {
                    $q->whereIn('id_karyawan_assignee', $karyawanIds)
                      ->orWhere('id_karyawan_creator', $karyawan->id);
                });
            } else {
                $tasksQuery->where(function ($q) use ($karyawan) {
                    $q->where('id_karyawan_assignee', $karyawan->id)
                      ->orWhere('id_karyawan_creator', $karyawan->id);
                });
            }
        } elseif (!$isAdmin && !$karyawan) {
            // Failsafe for non-admin without karyawan profile
            $tasksQuery->whereRaw('0 = 1');
        }

        $tasks = $tasksQuery->get()->toBase()->map(function ($task) {
            $color = 'gray';
            if ($task->status === 'Done') $color = 'green';
            elseif ($task->priority === 'High') $color = 'red';
            elseif ($task->priority === 'Medium') $color = 'orange';
            else $color = 'blue';

            return [
                'id' => 'task-' . $task->id,
                'title' => 'Tugas: ' . $task->title,
                'start' => $task->due_date->format('Y-m-d'),
                'type' => 'task',
                'color' => $color,
                'description' => 'Status: ' . $task->status . ' - Assignee: ' . ($task->assignee ? $task->assignee->nama_lengkap : '')
            ];
        });

        // Merge Events
        $events = $holidays->merge($leaves)->merge($companyEvents)->merge($tasks);

        $canManageEvents = $user->hasRole(Role::hrRoles());

        return Inertia::render('Admin/Calendar/Index', [
            'events' => $events,
            'currentMonth' => (int)$month,
            'currentYear' => (int)$year,
            'canManageEvents' => $canManageEvents
        ]);
    }
}
