<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApprovalRule;
use App\Models\ApprovalProcess;

class ApprovalCenterController extends Controller
{
    /**
     * Entry point for unified Approval Center.
     * Redirects the user to the first approval tab they have access to.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $karyawanId = $user->karyawan?->id;

        if (!$karyawanId) {
            abort(403, 'Akses ditolak. Anda tidak ditautkan dengan data Karyawan.');
        }

        // Check Cuti Approver
        $isCutiApprover = ApprovalRule::where('tipe', 'Cuti')->where('id_karyawan_approver', $karyawanId)->exists() 
            || ApprovalProcess::whereNotNull('id_cuti')->where('id_karyawan_target', $karyawanId)->where('status', 'Pending')->exists();

        if ($isCutiApprover) {
            return redirect()->route('admin.cuti.approvals');
        }

        // Check Pengajuan Approver
        $isPengajuanApprover = ApprovalRule::whereIn('tipe', ['Pengajuan', 'Pinjaman', 'Invoice'])->where('id_karyawan_approver', $karyawanId)->exists() 
            || ApprovalProcess::where(function($q) {
                $q->whereNotNull('id_pengajuan')->orWhereNotNull('id_pinjaman')->orWhereNotNull('id_invoice');
            })->where('id_karyawan_target', $karyawanId)->where('status', 'Pending')->exists();

        if ($isPengajuanApprover) {
            return redirect()->route('admin.pengajuan.approvals');
        }

        abort(403, 'Anda tidak memiliki hak akses ke menu Approval apapun.');
    }
}
