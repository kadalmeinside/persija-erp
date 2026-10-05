<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompanyEvent;
use App\Models\HariLibur;
use App\Models\PengajuanCuti;
use App\Models\Task;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|digits:4',
        ]);
        $month = (int) ($validated['month'] ?? Carbon::now()->month);
        $year = (int) ($validated['year'] ?? Carbon::now()->year);
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $karyawan = $request->user()->karyawan;
        $karyawanId = $karyawan ? $karyawan->id : null;
        $departemenId = $karyawan ? $karyawan->id_departemen : null;

        // 1. Events
        $events = CompanyEvent::where('start_date', '<=', $periodEnd)
            ->where('end_date', '>=', $periodStart)
            ->limit(500)->get()->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => $event->start_date->format('Y-m-d H:i:s'),
                'end_date' => $event->end_date->format('Y-m-d H:i:s'),
                'location' => $event->location,
                'color' => $event->color ?? '#007bff', // Blue
                'type' => 'event',
                'is_mine' => false,
                'status' => null
            ];
        });

        // 2. Holidays
        $holidays = HariLibur::whereBetween('tanggal', [$periodStart, $periodEnd])
                             ->limit(500)->get()->map(function ($holiday) {
            return [
                'id' => $holiday->id,
                'title' => $holiday->keterangan,
                'description' => null,
                'start_date' => $holiday->tanggal->format('Y-m-d 00:00:00'),
                'end_date' => $holiday->tanggal->format('Y-m-d 23:59:59'),
                'location' => null,
                'color' => '#dc3545', // Red
                'type' => 'holiday',
                'is_mine' => false,
                'status' => null
            ];
        });

        // 3. Leaves (Cuti User & Karyawan Lain di Departemen yang sama)
        $leavesQuery = PengajuanCuti::with('karyawan');
        
        // Filter by same department if the user is a karyawan
        if ($departemenId) {
            $leavesQuery->whereHas('karyawan', function ($q) use ($departemenId) {
                $q->where('id_departemen', $departemenId);
            });
        }

        $leaves = $leavesQuery->where('tgl_mulai', '<=', $periodEnd)
            ->where('tgl_selesai', '>=', $periodStart)
            ->limit(500)->get()->map(function ($cuti) use ($karyawanId) {
                // If it's not the user's own leave, only show if it's Approved
                if ($cuti->id_karyawan !== $karyawanId && $cuti->status !== 'Approved') {
                    return null;
                }

                $isMyLeave = $cuti->id_karyawan === $karyawanId;
                $title = $isMyLeave 
                    ? "Cuti Saya ({$cuti->status})" 
                    : 'Cuti: ' . ($cuti->karyawan?->nama_lengkap ?? 'Karyawan');

                // Orange for my leave, Gray for others' approved leaves
                $color = $isMyLeave ? '#fd7e14' : '#6c757d'; 

                return [
                    'id' => $cuti->id,
                    'title' => $title,
                    'description' => $cuti->alasan,
                    'start_date' => $cuti->tgl_mulai->format('Y-m-d 00:00:00'),
                    'end_date' => $cuti->tgl_selesai->format('Y-m-d 23:59:59'),
                    'location' => null,
                    'color' => $color,
                    'type' => 'leave',
                    'is_mine' => $isMyLeave,
                    'status' => $cuti->status
                ];
            })->filter()->values(); // Remove nulls

        // 4. Tasks assigned to user
        $tasks = collect([]);
        if ($karyawanId) {
            $tasks = Task::where('id_karyawan_assignee', $karyawanId)
                ->whereNotNull('due_date')
                ->where(function ($query) use ($month, $year) {
                    $query->whereMonth('due_date', $month)
                          ->whereYear('due_date', $year);
                })
                ->activeKanban() // Only active tasks
                ->limit(500)->get()->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => "Tugas: {$task->title}",
                        'description' => $task->description,
                        'start_date' => $task->due_date->format('Y-m-d 00:00:00'),
                        'end_date' => $task->due_date->format('Y-m-d 23:59:59'),
                        'location' => null,
                        'color' => '#28a745', // Green
                        'type' => 'task',
                        'is_mine' => true,
                        'status' => $task->status
                    ];
                });
        }

        // Combine and sort by start_date
        $merged = collect()
            ->concat($events)
            ->concat($holidays)
            ->concat($leaves)
            ->concat($tasks)
            ->sortBy('start_date')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $merged,
            'meta' => [
                'month' => (int)$month,
                'year' => (int)$year
            ]
        ]);
    }
}
