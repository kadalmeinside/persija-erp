<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pinjaman;
use App\Models\Karyawan;
use App\Services\PinjamanService;
use App\Services\ApprovalService;
use App\Enums\PengajuanStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PinjamanController extends Controller
{
    protected $pinjamanService;
    protected $approvalService;

    public function __construct(PinjamanService $pinjamanService, ApprovalService $approvalService)
    {
        $this->pinjamanService = $pinjamanService;
        $this->approvalService = $approvalService;
    }

    public function index(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $query = Pinjaman::query()->with(['karyawan', 'approver']);

        // Jika staf biasa (bukan HR/Finance/Direktur/Super Admin), hanya tampilkan pinjamannya sendiri
        if (!$user->hasAnyRole(['Super Admin', 'HR Manager', 'HR Staff', 'Direktur', 'Finance', 'Finance Manager', 'Finance Staff', 'Manajer Departemen'])) {
            if ($user->karyawan) {
                $query->where('id_karyawan', $user->karyawan->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->search) {
            $query->whereHas('karyawan', function ($q) use ($request) {
                $q->where('nama_lengkap', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $pinjaman = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $employees = Karyawan::whereIn('status_karyawan', ['Tetap', 'Kontrak'])->get(['id', 'nama_lengkap']);

        return Inertia::render('Admin/Pinjaman/Index', [
            'pinjaman' => $pinjaman,
            'employees' => $employees,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_karyawan' => 'required|exists:tbl_karyawan,id',
            'jumlah_pinjaman' => 'required|numeric|min:100000',
            'tenor_bulan' => 'required|integer|min:1|max:24',
            'tanggal_pengajuan' => 'required|date',
            'keterangan' => 'nullable|string'
        ]);

        try {
            $this->pinjamanService->createPinjaman($validated);

            return back()->with('success', 'Pengajuan pinjaman berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat pinjaman: ' . $e->getMessage());
        }
    }

    public function show(Pinjaman $pinjaman)
    {
        $pinjaman->load(['karyawan.departemen', 'approver', 'angsuran', 'approvalProcess.targetKaryawan.user', 'approvalProcess.actionKaryawan.user']);

        $user = \Illuminate\Support\Facades\Auth::user();
        $karyawanId = $user->karyawan ? $user->karyawan->id : null;

        // Otorisasi: HR, Finance, Direktur, Super Admin, pemilik pinjaman, atau approver dokumen ini
        $hasGeneralAccess = $user->hasAnyRole(['Super Admin', 'HR Manager', 'HR Staff', 'Direktur', 'Finance', 'Finance Manager', 'Finance Staff', 'Manajer Departemen']);
        $isOwner = $karyawanId && ($pinjaman->id_karyawan === $karyawanId);
        $isApprover = $karyawanId && $pinjaman->approvalProcess->contains(function ($p) use ($karyawanId) {
            return $p->id_karyawan_target === $karyawanId || $p->id_karyawan_action === $karyawanId;
        });

        if (!$hasGeneralAccess && !$isOwner && !$isApprover) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat pinjaman ini.');
        }

        $activities = $this->pinjamanService->getAuditLogs($pinjaman);

        // Check if current user is the target approver for the active step
        $activeStep = $pinjaman->approvalProcess->where('status', 'Pending')->sortBy('level_order')->first();
        $canApprove = false;
        
        if ($pinjaman->status === PengajuanStatus::PENDING_APPROVAL->value && $activeStep && $karyawanId) {
            $canApprove = ($activeStep->id_karyawan_target === $karyawanId);
        }


        return Inertia::render('Admin/Pinjaman/Show', [
            'pinjaman' => $pinjaman,
            'activities' => $activities,
            'permissions' => [
                'canApprove' => $canApprove
            ]
        ]);
    }

    public function action(Request $request, Pinjaman $pinjaman)
    {
        $request->validate([
            'status'  => 'required|in:Approved,Rejected,Revision',
            'catatan' => 'nullable|string|required_if:status,Rejected,Revision',
        ]);

        $user     = \Illuminate\Support\Facades\Auth::user();
        $karyawan = $user->karyawan;

        if (!$karyawan) {
            return redirect()->back()->with('error', 'Anda tidak terdaftar sebagai karyawan.');
        }

        try {
            match ($request->status) {
                'Approved' => [
                    $this->approvalService->approve($pinjaman, $karyawan->id, $request->catatan),
                    $message = 'Pinjaman berhasil disetujui (Approved).',
                ],
                'Rejected' => [
                    $this->approvalService->reject($pinjaman, $karyawan->id, $request->catatan),
                    $message = 'Pinjaman berhasil ditolak (Rejected).',
                ],
                'Revision' => [
                    $this->approvalService->revision($pinjaman, $karyawan->id, $request->catatan),
                    $message = 'Permintaan revisi berhasil dikirim ke pengaju.',
                ],
            };

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses pinjaman: ' . $e->getMessage());
        }
    }
    
    public function destroy(Pinjaman $pinjaman)
    {
        if ($pinjaman->status !== PengajuanStatus::DRAFT->value) {
            return back()->with('error', 'Hanya draft yang bisa dihapus.');
        }
        
        $pinjaman->delete();
        return back()->with('success', 'Data pinjaman dihapus.');
    }
}
