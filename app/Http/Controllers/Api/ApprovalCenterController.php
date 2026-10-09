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
        $karyawan = $request->user()->karyawan;

        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
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
        );

        return response()->json([
            'data' => $processes->getCollection()->map(
                fn (ApprovalProcess $process) => $this->serializeProcess($process)
            )->values(),
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
        $karyawan = $request->user()->karyawan;

        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        $isTarget = $approval->id_karyawan_target === $karyawan->id;
        $isActor = $approval->id_karyawan_action === $karyawan->id;

        if (!$isTarget && !$isActor) {
            return response()->json(['message' => 'Approval tidak dapat diakses.'], 403);
        }

        $approval->load([
            'targetKaryawan:id,nama_lengkap',
            'actionKaryawan:id,nama_lengkap',
            'approvalDocument',
        ]);

        return response()->json([
            'data' => [
                'approval' => $this->serializeProcess($approval),
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
        $karyawan = $request->user()->karyawan;

        if (!$karyawan || $approval->id_karyawan_target !== $karyawan->id) {
            return response()->json(['message' => 'Approval tidak dapat diproses oleh user ini.'], 403);
        }
        if ($approval->status !== 'Pending') {
            return response()->json(['message' => 'Approval sudah tidak berstatus pending.'], 422);
        }
        if (!$request->user()->pin || !Hash::check($validated['pin'], $request->user()->pin)) {
            return response()->json(['message' => 'PIN salah atau belum diatur.'], 422);
        }

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

        if ($document) {
            if ($type === 'Pengajuan') {
                $document->loadMissing(['pengaju:id,nama_lengkap', 'departemen:id,nama_departemen', 'detail.akunGl', 'detail.programKerja', 'karyawanPenerima:id,nama_lengkap']);
            } elseif ($type === 'Cuti') {
                $document->loadMissing(['karyawan:id,nama_lengkap', 'jenisCuti']);
            } elseif ($type === 'Pinjaman') {
                $document->loadMissing(['karyawan:id,nama_lengkap']);
            }
        }

        $applicant = match ($type) {
            'Cuti', 'Pinjaman' => $document?->karyawan,
            'Pengajuan' => $document?->pengaju,
            default => null,
        };

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
            'document' => $document ? array_merge(
                $document->toArray(),
                [
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
                ]
            ) : null,
            'catatan' => $process->catatan,
            'acted_at' => $process->tgl_aksi,
            'created_at' => $process->created_at,
        ];
    }

    private function timeline(ApprovalProcess $approval): array
    {
        $query = ApprovalProcess::with([
            'targetKaryawan:id,nama_lengkap',
            'actionKaryawan:id,nama_lengkap',
            'approvalDocument',
        ]);

        $query->where('id_approval_document', $approval->id_approval_document);

        return $query->orderBy('level_order')->get()->map(fn (ApprovalProcess $step) => [
            'id' => $step->id,
            'level_order' => $step->level_order,
            'label_aksi' => $step->label_aksi,
            'status' => $step->status,
            'target_employee' => $step->targetKaryawan?->nama_lengkap,
            'action_employee' => $step->actionKaryawan?->nama_lengkap,
            'catatan' => $step->catatan,
            'acted_at' => $step->tgl_aksi,
        ])->values()->all();
    }

    private function documentFor(ApprovalProcess $process)
    {
        return match (true) {
            $process->approvalDocument !== null => $process->approvalDocument->resolveDocument(),
            default => null,
        };
    }
}
