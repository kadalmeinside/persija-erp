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
        $colorHexMap = [
            'meeting' => '#9333ea',
            'general' => '#10b981',
            'training' => '#f59e0b',
            'online' => '#0ea5e9',
            'other' => '#6366f1',
            'purple' => '#9333ea',
            'emerald' => '#10b981',
            'amber' => '#f59e0b',
            'sky' => '#0ea5e9',
            'indigo' => '#6366f1',
        ];

        $events = CompanyEvent::where('start_date', '<=', $periodEnd)
            ->where('end_date', '>=', $periodStart)
            ->limit(500)->get()->map(function ($event) use ($colorHexMap) {
            $cat = $event->category ?? 'meeting';
            $rawColor = $event->color;
            $color = $colorHexMap[$rawColor] ?? ($colorHexMap[$cat] ?? '#9333ea');

            return [
                'id' => $event->id,
                'title' => $event->title,
                'category' => $cat,
                'description' => $event->description,
                'start_date' => $event->start_date->format('Y-m-d H:i:s'),
                'end_date' => $event->end_date->format('Y-m-d H:i:s'),
                'location' => $event->location,
                'color' => $color,
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

    /**
     * Store a new company event via mobile API.
     * Restricted to HR roles: Super Admin, HR Manager, HR Staff.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Ensure user has HR / Admin role
        if (!$user || !$user->hasRole(['Super Admin', 'HR Manager', 'HR Staff'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya role HR (Super Admin, HR Manager, HR Staff) yang dapat menambah agenda perusahaan.'
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|in:meeting,general,training,online,other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $category = $validated['category'] ?? 'meeting';
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = !empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : $startDate->copy()->addHour();

        if ($endDate->lte($startDate)) {
            $endDate = $startDate->copy()->addHour();
        }

        // Validate room conflict strictly for 'meeting' category
        if ($category === 'meeting' && !empty($validated['location'])) {
            $conflict = $this->findRoomConflict($validated['location'], $startDate, $endDate);
            if ($conflict) {
                $conflictStart = $conflict->start_date->format('d/m/Y H:i');
                $conflictEnd = $conflict->end_date ? $conflict->end_date->format('d/m/Y H:i') : $conflictStart;

                return response()->json([
                    'status' => 'error',
                    'message' => "Bentrok Ruang Rapat! Ruangan '{$conflict->location}' sudah digunakan untuk meeting '{$conflict->title}' pada jam {$conflictStart} s/d {$conflictEnd}. Silakan gunakan ruangan lain atau ubah jam rapat.",
                    'errors' => [
                        'location' => ["Ruangan '{$conflict->location}' sudah terisi jadwal rapat lain pada jam tersebut."]
                    ]
                ], 422);
            }
        }

        $colorMap = [
            'meeting' => '#9333ea',
            'general' => '#10b981',
            'training' => '#f59e0b',
            'online' => '#0ea5e9',
            'other' => '#6366f1',
        ];
        $color = $colorMap[$category] ?? '#9333ea';

        $event = CompanyEvent::create([
            'title' => $validated['title'],
            'category' => $category,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => $validated['description'] ?? null,
            'location' => !empty($validated['location']) ? trim($validated['location']) : null,
            'color' => $color,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Agenda berhasil ditambahkan.',
            'data' => [
                'id' => $event->id,
                'title' => $event->title,
                'category' => $event->category,
                'start_date' => $event->start_date->format('Y-m-d H:i:s'),
                'end_date' => $event->end_date->format('Y-m-d H:i:s'),
                'location' => $event->location,
                'description' => $event->description,
                'color' => $color,
                'type' => 'event',
            ]
        ], 201);
    }

    /**
     * Helper to detect room collisions for meeting agendas
     */
    protected function findRoomConflict(string $location, Carbon $start, Carbon $end, ?int $ignoreId = null): ?CompanyEvent
    {
        $normalizedLocation = trim($location);
        if (empty($normalizedLocation)) {
            return null;
        }

        $query = CompanyEvent::where(function ($q) {
                $q->where('category', 'meeting')
                  ->orWhereNull('category');
            })
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->whereRaw('LOWER(TRIM(location)) = ?', [strtolower($normalizedLocation)])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_date', '<', $end)
                  ->where(function ($sub) use ($start) {
                      $sub->where('end_date', '>', $start)
                          ->orWhere(function ($fallback) use ($start) {
                              $fallback->whereNull('end_date')
                                       ->where('start_date', '>=', $start);
                          });
                  });
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->first();
    }
}
