<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\PengajuanHeader;
use App\Models\User;

class PengajuanPolicy
{
    public function view(User $user, PengajuanHeader $pengajuan): bool
    {
        if ($user->hasAnyRole(array_merge(Role::financeRoles(), Role::adminRoles()))) {
            return true;
        }

        $karyawan = $user->karyawan;
        if (!$karyawan) {
            return false;
        }

        if ((int) $pengajuan->id_pengaju === (int) $karyawan->id || (int) $pengajuan->id_karyawan_penerima === (int) $karyawan->id) {
            return true;
        }

        return $pengajuan->approvalProcess()
            ->where(function ($query) use ($karyawan) {
                $query->where('id_karyawan_target', $karyawan->id)
                    ->orWhere('id_karyawan_action', $karyawan->id);
            })
            ->exists();
    }

    public function update(User $user, PengajuanHeader $pengajuan): bool
    {
        if ($user->hasRole(Role::SUPER_ADMIN->value)) {
            return true;
        }

        return $user->karyawan && (int) $pengajuan->id_pengaju === (int) $user->karyawan->id;
    }

    public function delete(User $user, PengajuanHeader $pengajuan): bool
    {
        return $this->update($user, $pengajuan);
    }

    public function cancel(User $user, PengajuanHeader $pengajuan): bool
    {
        if ($user->hasAnyRole(Role::financeRoles())) {
            return true;
        }

        return $this->update($user, $pengajuan);
    }
}
