<?php

namespace App\Http\Controllers\Admin\Pengajuan;

use App\Http\Controllers\Controller;
use App\Models\PengajuanHeader;
use App\Services\ApprovalService;
use App\Enums\PengajuanStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Display list of pengajuan needing approval.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $karyawan = $user->karyawan;
        
        if (!$karyawan) abort(403, 'Akses ditolak.');

        $view = $request->input('view', 'pending');
        
        $query = PengajuanHeader::with(['pengaju', 'departemen']);

        if ($view === 'history') {
            // Show approved/rejected by this user
            $query->whereHas('approvalProcess', function($q) use ($karyawan) {
                $q->where('id_karyawan_target', $karyawan->id)
                  ->whereIn('status', ['Approved', 'Rejected']);
            });
        } else {
            // Default: Show pending approvals
            $query->whereHas('approvalProcess', function($q) use ($karyawan) {
                $q->where('id_karyawan_target', $karyawan->id)
                  ->where('status', 'Pending');
            });
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nomor_pengajuan', 'like', '%' . $request->search . '%')
                  ->orWhereHas('pengaju', function($q2) use ($request) {
                      $q2->where('nama_lengkap', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $pengajuan = $query->orderBy('tgl_pengajuan', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Pengajuan/Index', [
            'pengajuan' => $pengajuan,
            'filters' => $request->only(['search', 'status', 'view']),
            'activeTab' => 'approvals',
            'approvalView' => $view, // Pass current view state
            'pin_session_valid' => session('approval_pin_verified_at') && \Carbon\Carbon::parse(session('approval_pin_verified_at'))->diffInMinutes(now()) <= 5,
        ]);
    }

    /**
     * Process Approve, Reject, atau Revision.
     */
    public function action(Request $request, PengajuanHeader $pengajuan)
    {
        $request->validate([
            'status'  => 'required|in:Approved,Rejected,Revision',
            'catatan' => 'nullable|string|required_if:status,Rejected,Revision',
        ]);

        $user     = Auth::user();
        $karyawan = $user->karyawan;

        if (!$karyawan) {
            return redirect()->back()->with('error', 'Anda tidak terdaftar sebagai karyawan.');
        }

        try {
            match ($request->status) {
                'Approved' => [
                    $this->approvalService->approve($pengajuan, $karyawan->id, $request->catatan),
                    $message = 'Pengajuan berhasil disetujui.',
                ],
                'Rejected' => [
                    $this->approvalService->reject($pengajuan, $karyawan->id, $request->catatan),
                    $message = 'Pengajuan berhasil ditolak.',
                ],
                'Revision' => [
                    $this->approvalService->revision($pengajuan, $karyawan->id, $request->catatan),
                    $message = 'Permintaan revisi berhasil dikirim ke pengaju.',
                ],
            };

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Approval Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }
}
