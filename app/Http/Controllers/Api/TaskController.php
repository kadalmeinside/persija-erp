<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskController extends Controller
{
    /**
     * Get active kanban tasks assigned to/created by the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $karyawan = Karyawan::where('user_id', $user->id)->first();

        if (!$karyawan) {
            return response()->json([
                'success' => false,
                'message' => 'User belum terdaftar sebagai Karyawan.'
            ], 403);
        }

        $isDirektur = $user->hasAnyRole(['direktur', 'Direktur']);

        // Base Query - only active tasks
        $query = Task::with(['creator:id,nama_lengkap', 'assignee:id,nama_lengkap'])
            ->activeKanban();
            
        if (!$isDirektur) {
            $query->where(function ($q) use ($karyawan) {
                $q->where('id_karyawan_assignee', $karyawan->id)
                  ->orWhere('id_karyawan_creator', $karyawan->id);
            });
        }
        
        $query->orderBy('order_index', 'asc')
            ->orderBy('created_at', 'desc');

        $tasks = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil data tugas aktif',
            'data' => $tasks
        ]);
    }

    /**
     * Get task history (all tasks including archived) with pagination
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        $karyawan = Karyawan::where('user_id', $user->id)->first();

        if (!$karyawan) {
            return response()->json([
                'success' => false,
                'message' => 'User belum terdaftar sebagai Karyawan.'
            ], 403);
        }

        $isDirektur = $user->hasAnyRole(['direktur', 'Direktur']);

        $query = Task::with(['creator:id,nama_lengkap', 'assignee:id,nama_lengkap']);

        if (!$isDirektur) {
            $query->where(function ($q) use ($karyawan) {
                $q->where('id_karyawan_assignee', $karyawan->id)
                  ->orWhere('id_karyawan_creator', $karyawan->id);
            });
        }

        $query->orderBy('created_at', 'desc');

        $tasks = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil riwayat tugas',
            'data' => $tasks
        ]);
    }

    /**
     * Store a newly created task
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $karyawan = Karyawan::where('user_id', $user->id)->first();

        if (!$karyawan) {
            return response()->json([
                'success' => false,
                'message' => 'User belum terdaftar sebagai Karyawan.'
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:Low,Medium,High',
            'due_date' => 'nullable|date',
            // Default to self-assign if not provided
            'id_karyawan_assignee' => 'nullable|exists:tbl_karyawan,id', 
        ]);

        $assigneeId = $validated['id_karyawan_assignee'] ?? $karyawan->id;
        if ($assigneeId !== $karyawan->id && !$this->canAssignToOthers($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menugaskan tugas kepada karyawan lain.',
            ], 403);
        }

        $assignee = Karyawan::whereKey($assigneeId)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('status_karyawan')
                    ->orWhereNotIn('status_karyawan', ['Nonaktif', 'Resign', 'Terminated']);
            })
            ->first();
        if (!$assignee) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan penerima tugas tidak aktif atau tidak ditemukan.',
            ], 422);
        }

        $validated['id_karyawan_creator'] = $karyawan->id;
        $validated['id_karyawan_assignee'] = $assigneeId;
        $validated['status'] = 'To Do';

        $task = Task::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dibuat',
            'data' => $task
        ]);
    }

    private function canAssignToOthers($user): bool
    {
        return $user->hasAnyRole([
            'Super Admin',
            'HR Manager',
            'Direktur',
            'Manajer Departemen',
        ]);
    }

    /**
     * Update task status (and optionally archive)
     */
    public function updateStatus(Request $request, $id)
    {
        $task = Task::find($id);
        
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas tidak ditemukan'
            ], 404);
        }

        // Verify authorization (only assignee or creator can update)
        $user = Auth::user();
        $karyawan = Karyawan::where('user_id', $user->id)->first();
        
        $isDirektur = $user->hasAnyRole(['direktur', 'Direktur']);
        $canUpdate = $karyawan && ($task->id_karyawan_assignee == $karyawan->id || $task->id_karyawan_creator == $karyawan->id || $isDirektur);

        if (!$canUpdate) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this task'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'nullable|in:To Do,In Progress,Review,Done',
            'archive' => 'nullable|boolean'
        ]);

        if (isset($validated['status'])) {
            $task->status = $validated['status'];
        }

        if (isset($validated['archive']) && $validated['archive'] == true) {
            $task->archived_at = Carbon::now();
        }

        $task->save();

        return response()->json([
            'success' => true,
            'message' => 'Status tugas berhasil diperbarui',
            'data' => $task
        ]);
    }
}
