<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalProcess;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalCenterController extends Controller
{
    /**
     * Entry point for unified Approval Center on Web.
     */
    public function index(Request $request)
    {
        $karyawan = $request->user()->karyawan;

        if (!$karyawan) {
            abort(403, 'Akses ditolak. Anda tidak ditautkan dengan data Karyawan.');
        }

        $query = ApprovalProcess::with([
            'targetKaryawan:id,nama_lengkap',
            'actionKaryawan:id,nama_lengkap',
            'approvalDocument',
        ])->where('id_karyawan_target', $karyawan->id);

        $status = $request->string('status')->toString() ?: 'Pending';
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $processes = $query->orderByDesc('created_at')->paginate(
            min($request->integer('per_page', 20), 100)
        )->withQueryString();

        $data = $processes->getCollection()->map(
            fn (ApprovalProcess $process) => $this->serializeProcess($process)
        )->values();

        $processes->setCollection($data);

        return Inertia::render('Admin/ApprovalCenter/Index', [
            'approvals' => $processes,
            'filters' => $request->only(['status', 'per_page']),
        ]);
    }

    private function serializeProcess(ApprovalProcess $process): array
    {
        $type = $process->approvalDocument?->document_type;
        $document = $this->documentFor($process);
        $applicant = match ($type) {
            'Cuti', 'Pinjaman' => $document?->karyawan,
            'Pengajuan' => $document?->pengaju,
            default => null,
        };

        // Resolve route name
        $routeName = '';
        if ($type === 'Cuti') $routeName = 'admin.cuti.show';
        if ($type === 'Pengajuan') $routeName = 'admin.pengajuan.show';
        if ($type === 'Pinjaman') $routeName = 'admin.pinjaman.show';
        if ($type === 'Invoice') $routeName = 'admin.invoice.show'; // if exists

        return [
            'id' => $process->id,
            'document_id' => $document?->id,
            'type' => $type,
            'status' => $process->status,
            'level_order' => $process->level_order,
            'label_aksi' => $process->label_aksi,
            'target_employee' => $process->targetKaryawan?->nama_lengkap,
            'action_employee' => $process->actionKaryawan?->nama_lengkap,
            'applicant' => $applicant?->nama_lengkap,
            'route_url' => ($routeName && $document) ? route($routeName, $document->id) : null,
            'document' => $document ? [
                'title' => $document->alasan
                    ?? $document->keterangan
                    ?? $document->nomor_pengajuan
                    ?? $document->nomor_invoice
                    ?? $type,
                'start_date' => $document->tgl_mulai ?? null,
                'end_date' => $document->tgl_selesai ?? null,
                'amount' => $document->total_nominal_diajukan
                    ?? $document->jumlah_pinjaman
                    ?? $document->total_tagihan
                    ?? null,
            ] : null,
            'catatan' => $process->catatan,
            'acted_at' => $process->tgl_aksi,
            'created_at' => $process->created_at,
        ];
    }

    private function documentFor(ApprovalProcess $process)
    {
        return match (true) {
            $process->approvalDocument !== null => $process->approvalDocument->resolveDocument(),
            default => null,
        };
    }
}
