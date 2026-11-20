<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Departemen;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function modify(User $user, Task $task): bool
    {
        if ($user->hasAnyRole(Role::adminRoles())) {
            return true;
        }

        $karyawan = $user->karyawan;
        if (!$karyawan) {
            return false;
        }

        if ((int) $task->id_karyawan_creator === (int) $karyawan->id || (int) $task->id_karyawan_assignee === (int) $karyawan->id) {
            return true;
        }

        $headDeptIds = Departemen::where('id_karyawan_kepala', $karyawan->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if (empty($headDeptIds)) {
            return false;
        }

        if ($task->id_departemen && in_array((int) $task->id_departemen, $headDeptIds, true)) {
            return true;
        }

        return $task->assignee && in_array((int) $task->assignee->id_departemen, $headDeptIds, true);
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->hasAnyRole(Role::adminRoles())) {
            return true;
        }

        return $user->karyawan && (int) $task->id_karyawan_creator === (int) $user->karyawan->id;
    }
}
