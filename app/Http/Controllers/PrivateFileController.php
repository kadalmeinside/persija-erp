<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\KaryawanDokumen;
use App\Models\PengajuanCuti;
use App\Models\PengajuanHeader;
use App\Models\PengajuanPembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PrivateFileController extends Controller
{
    public function dokumen(KaryawanDokumen $dokumen)
    {
        $user = Auth::user();
        $isHrOrAdmin = $user->hasAnyRole(array_merge(Role::hrRoles(), Role::adminRoles()));
        $isOwner = $user->karyawan && (int) $user->karyawan->id === (int) $dokumen->id_karyawan;

        abort_unless($isHrOrAdmin || $isOwner, 403, 'Anda tidak berwenang mengakses dokumen ini.');

        return $this->servePrivateFile($dokumen->file_path);
    }

    public function cutiAttachment(PengajuanCuti $cuti)
    {
        Gate::authorize('view', $cuti);

        return $this->servePrivateFile($cuti->lampiran);
    }

    public function paymentProof(PengajuanPembayaran $pembayaran)
    {
        $user = Auth::user();
        $canViewFinance = $user->hasAnyRole(array_merge(Role::financeRoles(), Role::adminRoles()));
        if (!$canViewFinance && $pembayaran->pengajuanHeader) {
            Gate::authorize('view', $pembayaran->pengajuanHeader);
        } elseif (!$canViewFinance) {
            abort(403);
        }

        return $this->servePrivateFile($pembayaran->bukti_bayar);
    }

    public function settlementFile(Request $request, PengajuanHeader $pengajuan)
    {
        Gate::authorize('view', $pengajuan);

        $path = (string) $request->query('path', '');
        abort_if($path === '' || str_contains($path, '..'), 404);

        $pengajuan->loadMissing('settlement.detail');
        $settlement = $pengajuan->settlement;
        abort_if(!$settlement, 404);

        $allowedPaths = collect([$settlement->bukti_pengembalian])
            ->merge($settlement->detail->pluck('file_bukti'))
            ->filter()
            ->all();

        abort_unless(in_array($path, $allowedPaths, true), 403);

        return $this->servePrivateFile($path);
    }

    private function servePrivateFile(?string $path)
    {
        abort_if(!$path || str_contains($path, '..'), 404);

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path);
        }

        abort(404, 'Berkas tidak ditemukan.');
    }
}
