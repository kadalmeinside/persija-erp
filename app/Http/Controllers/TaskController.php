<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\ProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $karyawan = Karyawan::where('user_id', $user->id)->first();

        if (!$karyawan) {
            abort(403, 'Anda belum terdaftar sebagai Karyawan.');
        }

        // 1. Is this employee a Department Head?
        $isHead = Departemen::where('id_karyawan_kepala', $karyawan->id)->exists();
        // Or if super admin
        $isAdmin = $user->hasRole(['super-admin', 'admin']);

        // Base Query
        $query = Task::with(['creator', 'assignee', 'departemen', 'programKerja'])
            ->orderBy('order_index', 'asc')
            ->orderBy('created_at', 'desc');

        if (!$isAdmin) {
            if ($isHead) {
                // Head sees tasks assigned to their department's staff OR created by them
                $departemenIds = Departemen::where('id_karyawan_kepala', $karyawan->id)->pluck('id')->toArray();
                $karyawanIds = Karyawan::whereIn('id_departemen', $departemenIds)->pluck('id')->toArray();
                $karyawanIds[] = $karyawan->id; // Ensure themselves

                $query->where(function ($q) use ($karyawanIds, $karyawan) {
                    $q->whereIn('id_karyawan_assignee', $karyawanIds)
                      ->orWhere('id_karyawan_creator', $karyawan->id);
                });
            } else {
                // Regular staff: only tasks they are assigned to or created
                $query->where(function ($q) use ($karyawan) {
                    $q->where('id_karyawan_assignee', $karyawan->id)
                      ->orWhere('id_karyawan_creator', $karyawan->id);
                });
            }
        }

        $tasks = $query->get();

        // Dropdown options based on hierarchy
        $assigneeOptions = collect();
        if ($isAdmin) {
            $assigneeOptions = Karyawan::select('id', 'nama_lengkap', 'jabatan')->get();
        } elseif ($isHead) {
            $departemenIds = Departemen::where('id_karyawan_kepala', $karyawan->id)->pluck('id')->toArray();
            $assigneeOptions = Karyawan::whereIn('id_departemen', $departemenIds)
                ->orWhere('id', $karyawan->id)
                ->select('id', 'nama_lengkap', 'jabatan')->distinct()->get();
        } else {
            $assigneeOptions = Karyawan::where('id', $karyawan->id)->select('id', 'nama_lengkap', 'jabatan')->get();
        }

        $departemenOptions = Departemen::select('id', 'nama_departemen')->get();
        $programKerjaOptions = ProgramKerja::select('id', 'nama_program')->get();

        return Inertia::render('Tasks/KanbanBoard', [
            'tasks' => $tasks,
            'assigneeOptions' => $assigneeOptions,
            'departemenOptions' => $departemenOptions,
            'programKerjaOptions' => $programKerjaOptions,
            'currentKaryawanId' => $karyawan->id,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $karyawan = Karyawan::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'id_karyawan_assignee' => 'required|exists:tbl_karyawan,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:Low,Medium,High',
            'due_date' => 'nullable|date',
            'id_departemen' => 'nullable|exists:tbl_departemen,id',
            'id_program_kerja' => 'nullable|exists:tbl_program_kerja,id',
        ]);

        $validated['id_karyawan_creator'] = $karyawan->id;
        $validated['status'] = 'To Do';

        Task::create($validated);

        return redirect()->back()->with('success', 'Tugas berhasil dibuat.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'id_karyawan_assignee' => 'required|exists:tbl_karyawan,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:To Do,In Progress,Review,Done',
            'priority' => 'required|in:Low,Medium,High',
            'due_date' => 'nullable|date',
            'id_departemen' => 'nullable|exists:tbl_departemen,id',
            'id_program_kerja' => 'nullable|exists:tbl_program_kerja,id',
        ]);

        $task->update($validated);

        return redirect()->back()->with('success', 'Tugas berhasil diperbarui.');
    }

    /**
     * Update task status (for drag and drop).
     */
    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:To Do,In Progress,Review,Done',
            'order_index' => 'nullable|integer',
        ]);

        $task->update($validated);
        
        // If there's batch ordering for drag and drop sorting within the same column
        if ($request->has('new_order') && is_array($request->new_order)) {
            foreach($request->new_order as $item) {
                if (isset($item['id']) && isset($item['order_index'])) {
                    Task::where('id', $item['id'])->update(['order_index' => $item['order_index']]);
                }
            }
        }

        return redirect()->back(); // inertia will reload data seamlessly
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->back()->with('success', 'Tugas berhasil dihapus.');
    }
}
