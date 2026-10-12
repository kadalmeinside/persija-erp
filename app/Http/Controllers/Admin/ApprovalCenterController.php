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

        // Status counts for badge counters and metric cards
        $baseQuery = ApprovalProcess::where('id_karyawan_target', $karyawan->id);
        $counts = [
            'pending' => (clone $baseQuery)->where('status', 'Pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'Approved')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'Rejected')->count(),
            'all' => (clone $baseQuery)->count(),
        ];

        $query = ApprovalProcess::with([
            'targetKaryawan.departemen',
            'actionKaryawan',
            'approvalDocument.pengajuan.document.pengaju.departemen',
            'approvalDocument.pengajuan.document.departemen',
            'approvalDocument.cuti.document.karyawan.departemen',
            'approvalDocument.cuti.document.jenisCuti',
            'approvalDocument.pinjaman.document.karyawan.departemen',
            'approvalDocument.invoice.document.pelanggan',
            'approvalDocument.invoice.document.departemen',
        ])->where('id_karyawan_target', $karyawan->id);

        $status = $request->string('status')->toString() ?: 'Pending';
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $type = $request->string('type')->toString();
        if ($type && $type !== 'all') {
            $query->whereHas('approvalDocument', fn ($q) => $q->where('document_type', $type));
        }

        $search = trim($request->string('search')->toString());
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('catatan', 'like', "%{$search}%")
                  ->orWhere('label_aksi', 'like', "%{$search}%")
                  ->orWhereHas('approvalDocument.pengajuan.document', function ($dq) use ($search) {
                      $dq->where('nomor_pengajuan', 'like', "%{$search}%")
                         ->orWhere('judul_pengajuan', 'like', "%{$search}%");
                  })
                  ->orWhereHas('approvalDocument.cuti.document', function ($dq) use ($search) {
                      $dq->where('alasan', 'like', "%{$search}%");
                  })
                  ->orWhereHas('approvalDocument.pinjaman.document', function ($dq) use ($search) {
                      $dq->where('keterangan', 'like', "%{$search}%");
                  })
                  ->orWhereHas('approvalDocument.invoice.document', function ($dq) use ($search) {
                      $dq->where('nomor_invoice', 'like', "%{$search}%");
                  });
            });
        }

        $processes = $query->orderByDesc('created_at')->paginate(
            min($request->integer('per_page', 15), 100)
        )->withQueryString();

        $data = $processes->getCollection()->map(
            fn (ApprovalProcess $process) => $this->serializeProcess($process)
        )->values();

        $processes->setCollection($data);

        return Inertia::render('Admin/ApprovalCenter/Index', [
            'approvals' => $processes,
            'counts' => $counts,
            'filters' => [
                'status' => $status,
                'type' => $type ?: 'all',
                'search' => $search,
                'per_page' => $request->integer('per_page', 15),
            ],
        ]);
    }

    private function serializeProcess(ApprovalProcess $process): array
    {
        $type = $process->approvalDocument?->document_type;
        $document = $this->documentFor($process);

        $docNumber = null;
        $title = null;
        $subType = null;
        $applicant = null;
        $applicantName = '-';
        $applicantDepartment = '-';
        $applicantPosition = '-';
        $applicantNik = '-';
        $applicantAvatar = null;
        $amount = null;
        $formattedAmount = null;
        $startDate = null;
        $endDate = null;
        $days = null;
        $tenor = null;
        $monthlyInstallment = null;
        $dueDate = null;
        $customerName = null;
        $description = null;
        $badgeInfo = null;

        if ($type === 'Pengajuan' && $document) {
            $docNumber = $document->nomor_pengajuan;
            $title = $document->judul_pengajuan ?: ($document->catatan_header ?: 'Pengajuan Dana');
            $subType = $document->tipe_pengajuan ?? 'Pengajuan Dana';
            $applicant = $document->pengaju;
            $applicantName = $applicant?->nama_lengkap ?? '-';
            $applicantDepartment = $document->departemen?->nama_departemen ?? $applicant?->departemen?->nama_departemen ?? '-';
            $applicantPosition = $applicant?->jabatan ?? '-';
            $applicantNik = $applicant?->nomor_induk_karyawan ?? '-';
            $applicantAvatar = $applicant?->foto_url ?? null;
            $amount = $document->total_nominal_diajukan ? (float) $document->total_nominal_diajukan : null;
            $formattedAmount = $amount !== null ? 'Rp ' . number_format($amount, 0, ',', '.') : null;
            $startDate = $document->tgl_pengajuan ? $document->tgl_pengajuan->format('Y-m-d') : null;
            $description = $document->catatan_header ?: $document->judul_pengajuan;
            $badgeInfo = $document->tipe_pengajuan ?? 'Pengajuan';
        } elseif ($type === 'Cuti' && $document) {
            $docNumber = 'CUTI-' . str_pad($document->id, 5, '0', STR_PAD_LEFT);
            $cutiName = $document->jenisCuti?->nama_cuti ?? 'Cuti';
            $title = "Permohonan {$cutiName}";
            $subType = $cutiName;
            $applicant = $document->karyawan;
            $applicantName = $applicant?->nama_lengkap ?? '-';
            $applicantDepartment = $applicant?->departemen?->nama_departemen ?? '-';
            $applicantPosition = $applicant?->jabatan ?? '-';
            $applicantNik = $applicant?->nomor_induk_karyawan ?? '-';
            $applicantAvatar = $applicant?->foto_url ?? null;
            $days = $document->jumlah_hari;
            $startDate = $document->tgl_mulai ? $document->tgl_mulai->format('Y-m-d') : null;
            $endDate = $document->tgl_selesai ? $document->tgl_selesai->format('Y-m-d') : null;
            $description = $document->alasan;
            $badgeInfo = "{$days} Hari Kerja";
        } elseif ($type === 'Pinjaman' && $document) {
            $docNumber = 'PINJ-' . str_pad($document->id, 5, '0', STR_PAD_LEFT);
            $title = "Pinjaman Karyawan ({$document->tenor_bulan} Bulan)";
            $subType = 'Pinjaman Karyawan';
            $applicant = $document->karyawan;
            $applicantName = $applicant?->nama_lengkap ?? '-';
            $applicantDepartment = $applicant?->departemen?->nama_departemen ?? '-';
            $applicantPosition = $applicant?->jabatan ?? '-';
            $applicantNik = $applicant?->nomor_induk_karyawan ?? '-';
            $applicantAvatar = $applicant?->foto_url ?? null;
            $amount = $document->jumlah_pinjaman ? (float) $document->jumlah_pinjaman : null;
            $formattedAmount = $amount !== null ? 'Rp ' . number_format($amount, 0, ',', '.') : null;
            $tenor = $document->tenor_bulan;
            $monthlyInstallment = $document->jumlah_angsuran_per_bulan ? (float) $document->jumlah_angsuran_per_bulan : null;
            $formattedInstallment = $monthlyInstallment !== null ? 'Rp ' . number_format($monthlyInstallment, 0, ',', '.') : null;
            $startDate = $document->tanggal_pengajuan ? $document->tanggal_pengajuan->format('Y-m-d') : null;
            $description = $document->keterangan;
            $badgeInfo = "Tenor {$tenor} Bln" . ($formattedInstallment ? " • {$formattedInstallment}/bln" : '');
        } elseif ($type === 'Invoice' && $document) {
            $docNumber = $document->nomor_invoice;
            $customerName = $document->pelanggan?->nama_pelanggan ?? $document->pelanggan?->nama_customer ?? 'Pelanggan';
            $title = "Invoice Penjualan: {$customerName}";
            $subType = 'Sales Invoice';
            $applicantName = $customerName;
            $applicantDepartment = $document->departemen?->nama_departemen ?? 'Keuangan & Penjualan';
            $applicantPosition = 'Pelanggan / Klien';
            $amount = $document->total_tagihan ? (float) $document->total_tagihan : null;
            $formattedAmount = $amount !== null ? 'Rp ' . number_format($amount, 0, ',', '.') : null;
            $startDate = $document->tgl_invoice ? $document->tgl_invoice->format('Y-m-d') : null;
            $dueDate = $document->tgl_jatuh_tempo ? $document->tgl_jatuh_tempo->format('Y-m-d') : null;
            $description = $document->catatan;
            $badgeInfo = "Jatuh Tempo: " . ($document->tgl_jatuh_tempo ? $document->tgl_jatuh_tempo->format('d/m/Y') : '-');
        }

        // Fallbacks if not detected
        $title = $title ?: ($document?->alasan ?? $document?->keterangan ?? $docNumber ?? $type ?? 'Dokumen');
        $docNumber = $docNumber ?: '#' . ($document?->id ?? $process->id);

        // Resolve route name
        $routeName = match ($type) {
            'Cuti' => 'admin.cuti.show',
            'Pengajuan' => 'admin.pengajuan.show',
            'Pinjaman' => 'admin.pinjaman.show',
            'Invoice' => 'admin.invoices.show',
            default => '',
        };

        $statusLabel = match ($process->status) {
            'Pending' => 'Butuh Disetujui',
            'Approved' => 'Disetujui',
            'Rejected' => 'Ditolak',
            default => $process->status,
        };

        return [
            'id' => $process->id,
            'document_id' => $document?->id,
            'type' => $type ?: 'Dokumen',
            'type_label' => match ($type) {
                'Pengajuan' => 'Pengajuan Dana',
                'Cuti' => 'Pengajuan Cuti',
                'Pinjaman' => 'Pinjaman Karyawan',
                'Invoice' => 'Invoice Penjualan',
                default => $type ?: 'Dokumen',
            },
            'sub_type' => $subType,
            'document_number' => $docNumber,
            'status' => $process->status,
            'status_label' => $statusLabel,
            'level_order' => $process->level_order,
            'label_aksi' => $process->label_aksi ?: 'Persetujuan Dokumen',
            'target_employee' => $process->targetKaryawan?->nama_lengkap,
            'action_employee' => $process->actionKaryawan?->nama_lengkap,
            'applicant' => $applicantName,
            'applicant_department' => $applicantDepartment,
            'applicant_position' => $applicantPosition,
            'applicant_nik' => $applicantNik,
            'applicant_avatar' => $applicantAvatar,
            'route_url' => ($routeName && $document && \Illuminate\Support\Facades\Route::has($routeName)) ? route($routeName, $document->id) : null,
            'details' => [
                'title' => $title,
                'description' => $description,
                'amount' => $amount,
                'formatted_amount' => $formattedAmount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days,
                'tenor' => $tenor,
                'monthly_installment' => $formattedInstallment ?? null,
                'due_date' => $dueDate,
                'customer' => $customerName,
                'department' => $applicantDepartment,
                'badge_info' => $badgeInfo,
            ],
            // Backward-compatible document object
            'document' => [
                'title' => $title,
                'code' => $docNumber,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'amount' => $amount,
            ],
            'catatan' => $process->catatan,
            'acted_at' => $process->tgl_aksi ? $process->tgl_aksi->format('d M Y, H:i') : null,
            'created_at' => $process->created_at ? $process->created_at->format('d M Y, H:i') : null,
            'created_at_human' => $process->created_at ? $process->created_at->diffForHumans() : null,
        ];
    }

    private function documentFor(ApprovalProcess $process)
    {
        $docWrapper = $process->approvalDocument;
        if (!$docWrapper) {
            return null;
        }

        return match ($docWrapper->document_type) {
            'Pengajuan' => $docWrapper->pengajuan?->document ?? $docWrapper->resolveDocument(),
            'Cuti' => $docWrapper->cuti?->document ?? $docWrapper->resolveDocument(),
            'Pinjaman' => $docWrapper->pinjaman?->document ?? $docWrapper->resolveDocument(),
            'Invoice' => $docWrapper->invoice?->document ?? $docWrapper->resolveDocument(),
            default => $docWrapper->resolveDocument(),
        };
    }
}
