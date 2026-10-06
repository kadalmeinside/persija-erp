<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Karyawan;
use App\Models\Departemen;
use Illuminate\Foundation\Auth\User;
use Illuminate\Database\Eloquent\Builder;

class TaskService
{
    /**
     * Build the base task query with role-based scoping.
     * 
     * @param User $user
     * @param array $relations
     * @param bool $activeOnly
     * @return Builder
     */
    public function buildScopedQuery(User $user, array $relations = [], bool $activeOnly = true): Builder
    {
        $karyawan = Karyawan::where('user_id', $user->id)->first();
        if (!$karyawan) {
            throw new \Exception('Anda belum terdaftar sebagai Karyawan.');
        }

        $query = Task::with($relations);
        
        if ($activeOnly) {
            $query->activeKanban();
        }

        $isAdminOrDirektur = $user->hasAnyRole(['super-admin', 'admin', 'direktur', 'Direktur']);
        
        if (!$isAdminOrDirektur) {
            $isHead = Departemen::where('id_karyawan_kepala', $karyawan->id)->exists();
            
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
        
        return $query;
    }

    /**
     * Get active kanban tasks.
     * 
     * @param User $user
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveTasks(User $user, array $relations = ['creator', 'assignee', 'departemen', 'programKerja'])
    {
        return $this->buildScopedQuery($user, $relations, true)
            ->orderBy('order_index', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get history tasks (including archived).
     * 
     * @param User $user
     * @param array $relations
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getHistoryTasks(User $user, array $relations = ['creator:id,nama_lengkap', 'assignee:id,nama_lengkap'], int $perPage = 15)
    {
        return $this->buildScopedQuery($user, $relations, false)
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
    }
}
