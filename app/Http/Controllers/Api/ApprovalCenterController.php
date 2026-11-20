<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalProcess;
use App\Models\SaldoCuti;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ApprovalCenterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $karyawan = $user->karyawan;

        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        $targetIds = $this->targetKaryawanIdsForUser($user, $karyawan);

        $baseQuery = ApprovalProcess::whereIn('id_karyawan_target', $targetIds);
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
        ])->whereIn('id_karyawan_target', $targetIds);

        $status = $request->string('status')->toString() ?: 'Pending';
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $type = $request->string('type')->toString();
        if ($type && $type !== 'all') {
            $query->whereHas('approvalDocument', fn ($q) => $q->where('document_type', $type));
        }

        $processes = $query->orderByDesc('created_at')->paginate(
            min($request->integer('per_page', 20), 100)
        );

        return response()->json([
            'data' => $processes->getCollection()->map(
                fn (ApprovalProcess $process) => $this->serializeProcess($process)
            )->values(),
            'counts' => $counts,
            'meta' => [
                'current_page' => $processes->currentPage(),
                'last_page' => $processes->lastPage(),
                'per_page' => $processes->perPage(),
                'total' => $processes->total(),
            ],
        ]);
    }

    public function show(Request $request, ApprovalProcess $approval)
    {
        $user = $request->user();
        $karyawan = $user->karyawan;

        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        $targetIds = $this->targetKaryawanIdsForUser($user, $karyawan);
        $isTarget = in_array((int) $approval->id_karyawan_target, $targetIds, true);
        $isActor = (int) $approval->id_karyawan_action === (int) $karyawan->id;

        if (!$isTarget && !$isActor) {
            return response()->json(['message' => 'Approval tidak dapat diakses.'], 403);
        }

        $approval->load([
            'targetKaryawan:id,nama_lengkap,jabatan',
            'actionKaryawan:id,nama_lengkap,jabatan',
            'approvalDocument',
        ]);

        $document = $this->documentFor($approval);

        return response()->json([
            'data' => [
                'approval' => $this->serializeProcess($approval),
                'document' => $document ? $document->toArray() : null,
                'timeline' => $this->timeline($approval),
            ],
        ]);
    }

    public function action(Request $request, ApprovalProcess $approval)
    {
        $validated = $request->validate([
            'status' => 'required|in:Approved,Rejected',
            'pin' => 'required|string|size:6',
            'catatan' => 'nullable|string|max:500',
        ]);
        $user = $request->user();
        $karyawan = $user->karyawan;

        if (!$karyawan) {
            return response()->json(['message' => 'Approval tidak dapat diproses oleh user ini.'], 403);
        }

        $targetIds = $this->targetKaryawanIdsForUser($user, $karyawan);
        if (!in_array((int) $approval->id_karyawan_target, $targetIds, true)) {
            return response()->json(['message' => 'Approval tidak dapat diproses oleh user ini.'], 403);
        }
        if ($approval->status !== 'Pending') {
            return response()->json(['message' => 'Approval sudah tidak berstatus pending.'], 422);
        }

        $throttleKey = 'pin-verify:' . $user->id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, \App\Services\PinVerificationService::MAX_ATTEMPTS)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            $minutes = (int) ceil($seconds / 60);
            return response()->json([
                'message' => "Terlalu banyak percobaan PIN gagal. Akun PIN terkunci selama {$minutes} menit.",
                'retry_after' => $seconds,
            ], 429);
        }

        if (!$user->pin) {
            return response()->json(['message' => 'Anda belum memiliki PIN.', 'has_pin' => false], 422);
        }
        if (!Hash::check($validated['pin'], $user->pin)) {
            \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, \App\Services\PinVerificationService::LOCKOUT_SECONDS);
            return response()->json(['message' => 'PIN salah.', 'has_pin' => true], 422);
        }
        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

        $model = $this->documentFor($approval);
        if (!$model) {
            return response()->json(['message' => 'Dokumen approval tidak ditemukan.'], 404);
        }

        $docStatus = $model->status ?? $model->status_global ?? null;
        // Depending on the enum or string, check if it's already finished
        if (in_array($docStatus, [
            'Rejected',
            'Cancelled',
            'Canceled',
            'Approved',
            \App\Enums\PengajuanStatus::REJECTED->value,
            \App\Enums\PengajuanStatus::APPROVED->value,
            \App\Enums\InvoiceStatus::Cancelled->value,
        ], true)) {
            return response()->json(['message' => 'Dokumen ini sudah selesai diproses ('.$docStatus.').'], 422);
        }

        $service = app(\App\Services\ApprovalService::class);
        try {
            if ($approval->approvalDocument?->document_type === 'Cuti' && $validated['status'] === 'Rejected') {
                DB::transaction(function () use ($approval, $service, $karyawan, $validated) {
                    $cuti = $approval->document();
                    if (!$cuti) {
                        abort(404, 'Dokumen cuti tidak ditemukan.');
                    }
                    $cuti = $approval->approvalDocument->document()->lockForUpdate()->firstOrFail();
                    $saldo = SaldoCuti::where([
                        'id_karyawan' => $cuti->id_karyawan,
                        'id_jenis_cuti' => $cuti->id_jenis_cuti,
                        'tahun_periode' => Carbon::parse($cuti->tgl_mulai)->year,
                    ])->lockForUpdate()->first();
                    if ($saldo && !$cuti->jenisCuti->is_unlimited) {
                        $saldo->decrement('saldo_terpakai', $cuti->jumlah_hari);
                        $saldo->increment('saldo_akhir', $cuti->jumlah_hari);
                    }
                    $service->reject($cuti, $karyawan->id, $validated['catatan'] ?? null);
                });
            } else {
                if ($validated['status'] === 'Approved') {
                    $service->approve($model, $karyawan->id, $validated['catatan'] ?? null);
                } else {
                    $service->reject($model, $karyawan->id, $validated['catatan'] ?? null);
                }
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Approval berhasil diproses.']);
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

        $title = $title ?: ($document?->alasan ?? $document?->keterangan ?? $docNumber ?? $type ?? 'Dokumen');
        $docNumber = $docNumber ?: '#' . ($document?->id ?? $process->id);

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
            'details' => [
                'title' => $title,
                'description' => $description,
                'amount' => $amount,
                'formatted_amount' => $formattedAmount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days,
                'tenor' => $tenor,
                'monthly_installment' => $monthlyInstallment ? 'Rp ' . number_format($monthlyInstallment, 0, ',', '.') : null,
                'due_date' => $dueDate,
                'customer' => $customerName,
                'department' => $applicantDepartment,
                'badge_info' => $badgeInfo,
            ],
            'document' => $document ? array_merge(
                $document->toArray(),
                [
                    'title' => $title,
                    'code' => $docNumber,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'amount' => $amount,
                    'formatted_amount' => $formattedAmount,
                ]
            ) : null,
            'catatan' => $process->catatan,
            'acted_at' => $process->tgl_aksi ? $process->tgl_aksi->format('d M Y, H:i') : null,
            'created_at' => $process->created_at ? $process->created_at->format('d M Y, H:i') : null,
            'created_at_human' => $process->created_at ? $process->created_at->diffForHumans() : null,
        ];
    }

    private function timeline(ApprovalProcess $approval): array
    {
        $query = ApprovalProcess::with([
            'targetKaryawan:id,nama_lengkap,jabatan',
            'actionKaryawan:id,nama_lengkap,jabatan',
            'approvalDocument',
        ]);

        $query->where('id_approval_document', $approval->id_approval_document);

        return $query->orderBy('level_order')->get()->map(fn (ApprovalProcess $step) => [
            'id' => $step->id,
            'level_order' => $step->level_order,
            'label_aksi' => $step->label_aksi,
            'status' => $step->status,
            'target_employee' => $step->targetKaryawan?->nama_lengkap,
            'target_position' => $step->jabatan_approver ?: $step->targetKaryawan?->jabatan,
            'action_employee' => $step->actionKaryawan?->nama_lengkap,
            'action_position' => $step->actionKaryawan?->jabatan,
            'catatan' => $step->catatan,
            'acted_at' => $step->tgl_aksi ? $step->tgl_aksi->toISOString() : null,
            'created_at' => $step->created_at ? $step->created_at->toISOString() : null,
        ])->values()->all();
    }

    private function documentFor(ApprovalProcess $process)
    {
        $docWrapper = $process->approvalDocument;
        if (!$docWrapper) {
            return null;
        }

        $doc = match ($docWrapper->document_type) {
            'Pengajuan' => $docWrapper->pengajuan?->document ?? $docWrapper->resolveDocument(),
            'Cuti' => $docWrapper->cuti?->document ?? $docWrapper->resolveDocument(),
            'Pinjaman' => $docWrapper->pinjaman?->document ?? $docWrapper->resolveDocument(),
            'Invoice' => $docWrapper->invoice?->document ?? $docWrapper->resolveDocument(),
            default => $docWrapper->resolveDocument(),
        };

        if ($doc instanceof \App\Models\PengajuanHeader) {
            $doc->loadMissing([
                'pengaju.departemen',
                'departemen',
                'vendorPenerima',
                'karyawanPenerima',
                'detail.programKerja',
                'detail.akunGl',
                'detail.pajak',
                'approvalProcess.targetKaryawan',
                'approvalProcess.actionKaryawan',
            ]);
        }

        return $doc;
    }

    /**
     * List active/past delegations and eligible delegate candidates for mobile user.
     */
    public function delegations(Request $request)
    {
        $user = $request->user();

        $delegations = \App\Models\ApprovalDelegation::with([
            'delegator:id,name',
            'delegate:id,name',
        ])
            ->where(function ($q) use ($user) {
                $q->where('delegator_user_id', $user->id)
                    ->orWhere('delegate_user_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'delegator_user_id' => $d->delegator_user_id,
                'delegate_user_id' => $d->delegate_user_id,
                'delegator_name' => $d->delegator?->name ?? '-',
                'delegate_name' => $d->delegate?->name ?? '-',
                'start_date' => $d->start_date?->format('Y-m-d'),
                'end_date' => $d->end_date?->format('Y-m-d'),
                'reason' => $d->reason,
                'is_active' => (bool) $d->is_active,
                'is_currently_active' => $d->isCurrentlyActive(),
                'is_outgoing' => (int) $d->delegator_user_id === (int) $user->id,
            ]);

        $candidates = \App\Models\User::with('karyawan:id,user_id,jabatan')
            ->whereHas('karyawan')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'jabatan' => $u->karyawan?->jabatan ?? 'Staf',
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $delegations,
            'candidates' => $candidates,
        ]);
    }

    /**
     * Create a temporary approval delegation from mobile approver.
     */
    public function storeDelegation(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'delegate_user_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        if ((int) $validated['delegate_user_id'] === (int) $user->id) {
            return response()->json([
                'message' => 'Anda tidak dapat mendelegasikan persetujuan kepada diri sendiri.',
            ], 422);
        }

        $delegation = \App\Models\ApprovalDelegation::create([
            'delegator_user_id' => $user->id,
            'delegate_user_id' => $validated['delegate_user_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'] ?? null,
            'is_active' => true,
        ]);

        \App\Services\ImmutableAuditService::log(
            action: 'APPROVAL_DELEGATION_CREATED',
            entityType: 'ApprovalDelegation',
            entityId: (string) $delegation->id,
            payload: [
                'delegator_user_id' => $user->id,
                'delegate_user_id' => (int) $validated['delegate_user_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ],
            actorId: $user->id,
            actorName: $user->name,
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Delegasi approval sementara berhasil disimpan.',
            'data' => $delegation,
        ], 201);
    }

    /**
     * Revoke an active delegation created by the authenticated user.
     */
    public function revokeDelegation(Request $request, $id)
    {
        $user = $request->user();
        $delegation = \App\Models\ApprovalDelegation::findOrFail($id);

        if ((int) $delegation->delegator_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Anda tidak berwenang menonaktifkan delegasi ini.'], 403);
        }

        $delegation->update(['is_active' => false]);

        \App\Services\ImmutableAuditService::log(
            action: 'APPROVAL_DELEGATION_REVOKED',
            entityType: 'ApprovalDelegation',
            entityId: (string) $delegation->id,
            payload: ['delegator_user_id' => $user->id],
            actorId: $user->id,
            actorName: $user->name,
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Delegasi approval berhasil dinonaktifkan.',
        ]);
    }

    /**
     * Resolve employee IDs whose approvals this user may inspect/process (self + active delegators).
     *
     * @return array<int, int>
     */
    private function targetKaryawanIdsForUser(\App\Models\User $user, \App\Models\Karyawan $karyawan): array
    {
        $ids = [(int) $karyawan->id];
        $today = Carbon::today()->toDateString();

        $delegations = \App\Models\ApprovalDelegation::where('delegate_user_id', $user->id)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with('delegator.karyawan:id,user_id')
            ->get();

        foreach ($delegations as $delegation) {
            $delegatorKaryawanId = $delegation->delegator?->karyawan?->id;
            if ($delegatorKaryawanId) {
                $ids[] = (int) $delegatorKaryawanId;
            }
        }

        return array_values(array_unique($ids));
    }
}
