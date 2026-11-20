<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\PengajuanCuti;
use App\Models\User;

class CutiPolicy
{
    public function view(User $user, PengajuanCuti $cuti): bool
    {
        if ($user->hasAnyRole(array_merge(Role::hrRoles(), Role::adminRoles()))) {
            return true;
        }

        $karyawan = $user->karyawan;
        if (!$karyawan) {
            return false;
        }

        if ((int) $cuti->id_karyawan === (int) $karyawan->id) {
            return true;
        }

        return $cuti->approvalProcess()
            ->where(function ($query) use ($karyawan) {
                $query->where('id_karyawan_target', $karyawan->id)
                    ->orWhere('id_karyawan_action', $karyawan->id);
            })
            ->exists();
    }
}
