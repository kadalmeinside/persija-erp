<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InternalTransfer;
use App\Models\JurnalDetail;
use App\Models\KasBank;
use App\Services\DocumentNumberService;
use App\Services\GLService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InternalTransferController extends Controller
{
    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $query = InternalTransfer::with([
            'fromKasBank:id,nama_bank,nomor_rekening',
            'toKasBank:id,nama_bank,nomor_rekening',
            'creator:id,name',
            'approver:id,name',
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->start_date) {
            $query->whereDate('tgl_transfer', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('tgl_transfer', '<=', $request->end_date);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nomor_transfer', 'like', '%' . $request->search . '%')
                  ->orWhere('keterangan', 'like', '%' . $request->search . '%');
            });
        }

        $transfers = $query->orderBy('tgl_transfer', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Finance/InternalTransfer/Index', [
            'transfers' => $transfers,
            'filters'   => $request->only(['status', 'start_date', 'end_date', 'search']),
            'summary'   => [
                'draft'    => InternalTransfer::where('status', 'Draft')->count(),
                'approved' => InternalTransfer::where('status', 'Approved')->count(),
            ],
        ]);
    }

    // =========================================================================
    // CREATE / STORE
    // =========================================================================

    public function create()
    {
        $kasBankService = app(\App\Services\KasBankService::class);
        $kasBanks = KasBank::where('is_active', true)
            ->orderBy('nama_bank')
            ->get(['id', 'nama_bank', 'nomor_rekening', 'id_akun_gl', 'saldo_awal'])
            ->map(function ($kb) use ($kasBankService) {
                return array_merge($kb->toArray(), ['saldo_gl' => $kasBankService->getSaldoGl($kb)]);
            });

        return Inertia::render('Admin/Finance/InternalTransfer/Create', [
            'kasBanks' => $kasBanks,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tgl_transfer'    => 'required|date',
            'from_kas_bank_id'=> 'required|exists:tbl_kas_bank,id|different:to_kas_bank_id',
            'to_kas_bank_id'  => 'required|exists:tbl_kas_bank,id',
            'nominal'         => 'required|numeric|min:1',
            'keterangan'      => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                InternalTransfer::create([
                    'nomor_transfer'   => DocumentNumberService::internalTransfer(),
                    'tgl_transfer'     => $validated['tgl_transfer'],
                    'from_kas_bank_id' => $validated['from_kas_bank_id'],
                    'to_kas_bank_id'   => $validated['to_kas_bank_id'],
                    'nominal'          => $validated['nominal'],
                    'keterangan'       => $validated['keterangan'] ?? null,
                    'status'           => 'Draft',
                    'created_by'       => Auth::id(),
                ]);
            });

            return redirect()->route('admin.internal-transfers.index')
                ->with('success', 'Internal Transfer berhasil dibuat dan menunggu persetujuan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat transfer: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show(InternalTransfer $internalTransfer)
    {
        $internalTransfer->load([
            'fromKasBank.akunGl',
            'toKasBank.akunGl',
            'creator',
            'approver',
        ]);

        return Inertia::render('Admin/Finance/InternalTransfer/Show', [
            'transfer' => $internalTransfer,
        ]);
    }

    // =========================================================================
    // APPROVE
    // =========================================================================

    /**
     * Approver menyetujui Internal Transfer.
     * Saat ini, siapa pun yang punya akses Finance dapat approve.
     * GL posting dilakukan saat approve.
     *
     * GL:
     *   Dr. KasBank Tujuan   Rp X
     *       Cr. KasBank Asal Rp X
     */
    public function approve(Request $request, InternalTransfer $internalTransfer)
    {
        abort_unless(
            Auth::user()->hasAnyRole([
                \App\Enums\Role::SUPER_ADMIN->value,
                \App\Enums\Role::FINANCE_MANAGER->value,
                \App\Enums\Role::DIREKTUR->value,
            ]),
            403,
            'Hanya Finance Manager, Direktur, atau Super Admin yang dapat menyetujui Internal Transfer.'
        );

        app(\App\Services\PinVerificationService::class)->assertVerified($request, Auth::user());

        try {
            DB::transaction(function () use ($internalTransfer) {
                $lockedTransfer = InternalTransfer::where('id', $internalTransfer->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$lockedTransfer->isDraft()) {
                    throw new \Exception('Hanya transfer berstatus Draft yang dapat disetujui.');
                }

                if ($lockedTransfer->created_by && (int) $lockedTransfer->created_by === (int) Auth::id()) {
                    throw new \DomainException('Pembuat transfer tidak dapat menyetujui transfer yang sama (Segregation of Duties).');
                }

                $lockedTransfer->load(['fromKasBank.akunGl', 'toKasBank.akunGl']);

                $from = $lockedTransfer->fromKasBank;
                $to   = $lockedTransfer->toKasBank;

                if (!$from->id_akun_gl) {
                    throw new \Exception("Rekening asal '{$from->nama_bank}' belum terhubung ke Akun GL.");
                }
                if (!$to->id_akun_gl) {
                    throw new \Exception("Rekening tujuan '{$to->nama_bank}' belum terhubung ke Akun GL.");
                }

                // ─── HARD BLOCK: Cek saldo GL rekening asal ──────────────────
                $saldoFrom = app(\App\Services\KasBankService::class)->getSaldoGl($from);

                if ($saldoFrom < $lockedTransfer->nominal) {
                    throw new \Exception(
                        "Saldo rekening '{$from->nama_bank}' tidak mencukupi. "
                        . "Saldo tersedia: Rp " . number_format($saldoFrom, 0, ',', '.') . ", "
                        . "nominal transfer: Rp " . number_format($lockedTransfer->nominal, 0, ',', '.') . "."
                    );
                }

                // Posting GL
                $jurnal = GLService::createJournal(
                    $lockedTransfer->tgl_transfer,
                    'Internal Transfer: ' . $lockedTransfer->nomor_transfer,
                    [
                        [
                            'id_akun'    => $to->id_akun_gl,
                            'debit'      => $lockedTransfer->nominal,
                            'kredit'     => 0,
                            'keterangan' => "Transfer masuk dari {$from->nama_bank}",
                        ],
                        [
                            'id_akun'    => $from->id_akun_gl,
                            'debit'      => 0,
                            'kredit'     => $lockedTransfer->nominal,
                            'keterangan' => "Transfer keluar ke {$to->nama_bank}",
                        ],
                    ],
                    'IT',
                    $lockedTransfer->id,
                    'InternalTransfer'
                );

                $lockedTransfer->update([
                    'status'      => 'Approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                    'id_jurnal'   => $jurnal->id ?? null,
                ]);

                \App\Services\ImmutableAuditService::log(
                    action: 'INTERNAL_TRANSFER_APPROVED',
                    entityType: 'InternalTransfer',
                    entityId: (string) $lockedTransfer->id,
                    payload: [
                        'nomor_transfer' => $lockedTransfer->nomor_transfer,
                        'nominal' => (float) $lockedTransfer->nominal,
                        'from_kas_bank_id' => $lockedTransfer->from_kas_bank_id,
                        'to_kas_bank_id' => $lockedTransfer->to_kas_bank_id,
                    ],
                    actorId: Auth::id(),
                    actorName: Auth::user()?->name
                );
            });

            return back()->with('success', 'Internal Transfer berhasil disetujui dan GL telah diposting.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyetujui transfer: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // CANCEL
    // =========================================================================

    public function cancel(InternalTransfer $internalTransfer)
    {
        if (!$internalTransfer->isDraft()) {
            return back()->with('error', 'Hanya transfer berstatus Draft yang dapat dibatalkan. Transfer yang sudah disetujui tidak dapat di-void.');
        }

        try {
            $internalTransfer->update(['status' => 'Cancelled']);
            return back()->with('success', 'Internal Transfer berhasil dibatalkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membatalkan transfer: ' . $e->getMessage());
        }
    }
}
