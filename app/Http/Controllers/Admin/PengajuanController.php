<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanHeader;
use App\Models\PengajuanDetail;
use App\Models\AkunGl;
use App\Models\ProgramKerja;
use App\Models\TaxType;
use App\Models\Karyawan;
use App\Models\BudgetMaster;
use App\Models\PeriodeAnggaran;
use App\Models\Vendor;
use App\Models\PengajuanPembayaran;
use App\Models\KasBank;
use App\Enums\PengajuanStatus;
use App\Enums\Role;
use App\Http\Requests\Pengajuan\StorePengajuanRequest;
use App\Http\Requests\Pengajuan\UpdatePengajuanRequest;
use App\Services\PengajuanService;
use App\Services\BudgetCheckService;
use App\Services\ApprovalService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class PengajuanController extends Controller
{
    protected $pengajuanService;
    protected $budgetCheckService;
    protected $approvalService;

    public function __construct(
        PengajuanService $pengajuanService,
        BudgetCheckService $budgetCheckService,
        ApprovalService $approvalService
    ) {
        $this->pengajuanService = $pengajuanService;
        $this->budgetCheckService = $budgetCheckService;
        $this->approvalService = $approvalService;
    }

    /**
     * Mendapatkan ID Periode Anggaran yang aktif saat ini.
     */
    private function getActivePeriodeId($date = null)
    {
        if ($date) {
            $periode = PeriodeAnggaran::whereDate('tanggal_mulai', '<=', $date)
                        ->whereDate('tanggal_selesai', '>=', $date)
                        ->first();
            
            if ($periode) return $periode->id;
        }

        $periode = PeriodeAnggaran::where('is_active', true)->first();
        
        return $periode ? $periode->id : null;
    }

    /**
     * [AJAX] Mengambil daftar Program Kerja berdasarkan Departemen.
     */
    public function getProgramsByDepartemen(Request $request)
    {
        $request->validate(['id_departemen' => 'required|integer']);

        // Fetch programs belonging to the department
        // We query ProgramKerja directly as it has id_departemen
        $programs = ProgramKerja::where('id_departemen', $request->id_departemen)
                    ->orderBy('nama_program')
                    ->get(['id', 'nama_program']);

        return response()->json($programs);
    }

    /**
     * [AJAX] Mengambil daftar Akun GL yang memiliki budget di Program tertentu.
     */

    public function getAccountsByProgram(Request $request)
    {
        $request->validate([
            'id_departemen' => 'required|integer', 
            'id_program' => 'required|integer'
        ]);

        $tgl = $request->tgl_pengajuan ? Carbon::parse($request->tgl_pengajuan) : now();
        $periodeId = $this->getActivePeriodeId($tgl);
        
        if (!$periodeId) return response()->json([]);

        // 1. Get Budgeted Accounts (via BudgetMaster -> PosAnggaran)
        $budgetedAccounts = \App\Models\BudgetMaster::where('id_periode_anggaran', $periodeId)
            ->whereHas('posAnggaran', function($q) use ($request) {
                $q->where('id_program_kerja', $request->id_program);
            })
            ->with('posAnggaran.akunGl')
            ->get()
            ->pluck('posAnggaran.akunGl')
            ->unique('id')
            ->values();

        // 2. If this is a Tax Payment context (we can pass a flag), include Liability Accounts
        // For now, let's always include Liability accounts if they are mapped to the program (PosAnggaran)
        // OR just return all Liability accounts if requested?
        // Let's stick to Budgeted Accounts for normal flow.
        // For Tax Payment, we might need a separate endpoint or just append them here?
        // Let's append 'Utang' accounts that are mapped to this program in PosAnggaran, even if no BudgetMaster exists.
        
        $nonBudgetedAccounts = \App\Models\PosAnggaran::where('id_program_kerja', $request->id_program)
            ->where('is_active', true)
            ->whereHas('akunGl', function($q) {
                $q->whereIn('tipe_akun', ['Utang', 'Kewajiban']);
            })
            ->with('akunGl')
            ->get()
            ->pluck('akunGl')
            ->unique('id')
            ->values();
            
        $merged = $budgetedAccounts->merge($nonBudgetedAccounts)->unique('id')->values();



        return response()->json($merged);
    }

    /**
     * [AJAX] Get Mapped Program for Tax Account.
     */
    public function getTaxProgram(Request $request)
    {
        $request->validate(['id_akun' => 'required|integer']);

        // Find TaxType linked to this Account
        $taxType = \App\Models\TaxType::where('id_akun_gl', $request->id_akun)->first();

        if ($taxType && $taxType->id_program) {
            return response()->json(['id_program' => $taxType->id_program]);
        }

        return response()->json(['id_program' => null]);
    }

    /**
     * [AJAX] Cek Sisa Saldo Anggaran Real-time.
     */
    public function getBudgetBalance(Request $request)
    {
        $validator = $request->validate([
            'id_departemen' => 'required|integer',
            'id_akun' => 'required|integer',
            'id_program' => 'required|integer',
            'tgl_pengajuan' => 'required|date',
        ]);

        $result = $this->budgetCheckService->getSisaSaldoDB(
            $validator['id_departemen'],
            $validator['id_akun'],
            $validator['id_program'],
            Carbon::parse($validator['tgl_pengajuan'])
        );

        return response()->json($result);
    }

    /**
     * [AJAX] Simpan Vendor Baru + Rekening Bank.
     */
    public function storeVendor(Request $request)
    {
        $validator = $request->validate([
            'nama_vendor' => 'required|string|max:255',
            'nama_bank' => 'required|string|max:100',
            'nomor_rekening' => 'required|string|max:50',
            'atas_nama_rekening' => 'required|string|max:255',
            'no_telp' => 'nullable|string|max:20',
        ]);

        try {
            $vendor = DB::transaction(function () use ($validator) {
                $newVendor = Vendor::create([
                    'kode_vendor' => DocumentNumberService::vendor(),
                    'nama_vendor' => $validator['nama_vendor'],
                    'telepon_vendor' => $validator['no_telp'] ?? null,
                    'kategori_vendor' => 'Umum',
                    'is_active' => 1,
                ]);

                $newVendor->rekeningBank()->create([
                    'nama_bank' => $validator['nama_bank'],
                    'nomor_rekening' => $validator['nomor_rekening'],
                    'atas_nama_rekening' => $validator['atas_nama_rekening'],
                    'is_primary' => true,
                ]);

                return $newVendor;
            });

            $vendor->load('rekeningBank');
            $vendor->append('primary_bank');

            return response()->json(['success' => true, 'vendor' => $vendor]);
        } catch (\Exception $e) {
            Log::error("Error storeVendor: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [AJAX] Simpan Rekening Bank Karyawan Baru.
     */
    public function storeEmployeeBank(Request $request)
    {
        $validator = $request->validate([
            'id_karyawan' => 'required|exists:tbl_karyawan,id',
            'nama_bank' => 'required|string|max:100',
            'nomor_rekening' => 'required|string|max:50',
            'atas_nama_rekening' => 'required|string|max:255',
            'cabang' => 'nullable|string|max:100',
        ]);

        try {
            $bank = DB::transaction(function () use ($validator) {
                $karyawan = Karyawan::find($validator['id_karyawan']);
                // Set bank lama jadi non-primary
                $karyawan->rekeningBank()->update(['is_primary' => false]);
                // Buat bank baru
                return $karyawan->rekeningBank()->create([
                    'nama_bank' => $validator['nama_bank'],
                    'nomor_rekening' => $validator['nomor_rekening'],
                    'atas_nama_rekening' => $validator['atas_nama_rekening'],
                    'cabang' => $validator['cabang'] ?? null,
                    'is_primary' => true,
                ]);
            });

            return response()->json(['success' => true, 'bank' => $bank]);
        } catch (\Exception $e) {
            Log::error("Error storeEmployeeBank: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // CRUD METHODS
    // =========================================================================

    /**
     * Index Page.
     */


    /**
     * 3. All Requests (Finance/Admin View)
     */
    public function index(Request $request)
    {
        // If not Finance/Super Admin, redirect to appropriate view
        if (!Auth::user()->hasRole(Role::financeRoles())) {
             return redirect()->route('admin.pengajuan.my-requests');
        }

        $query = PengajuanHeader::with(['pengaju', 'departemen', 'currentStep', 'laporanPenggunaan']);

        // Search Filter
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nomor_pengajuan', 'like', '%' . $request->search . '%')
                  ->orWhereHas('pengaju', function($q2) use ($request) {
                      $q2->where('nama_lengkap', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Status Filter
        if ($request->status) {
            $query->where('status_global', $request->status);
        }

        // Tipe Filter
        if ($request->tipe) {
            $query->where('tipe_pengajuan', $request->tipe);
        }

        // Laporan Filter
        if ($request->laporan) {
            if ($request->laporan === 'butuh') {
                $query->butuhLaporan();
            } elseif ($request->laporan === 'ada') {
                $query->whereHas('laporanPenggunaan');
            }
        }

        $perPage = $request->per_page ? (int)$request->per_page : 15;
        if ($perPage > 100) $perPage = 100; // Limit max pagination
        $pengajuan = $query->orderBy('tgl_pengajuan', 'desc')->paginate($perPage)->withQueryString();

        // Calculate can_approve for each item
        $user = Auth::user();
        $userKaryawanId = $user->karyawan ? $user->karyawan->id : null;
        $isSuperAdmin = $user->hasRole('Super Admin');

        $pengajuan->getCollection()->transform(function ($item) use ($userKaryawanId, $isSuperAdmin) {
            $canApprove = false;
            // Use strict Enum comparison or check value
            if ($item->status_global === \App\Enums\PengajuanStatus::PENDING_APPROVAL && $item->currentStep) {
                // Check if current user is target
                if ($userKaryawanId && $item->currentStep->id_karyawan_target == $userKaryawanId) {
                    $canApprove = true;
                }
                // Super Admin override -> REMOVED for Strict Mode
                // if ($isSuperAdmin) {
                //    $canApprove = true;
                // }
            }
            $item->can_approve = $canApprove;
            return $item;
        });

        return Inertia::render('Admin/Pengajuan/Index', [
            'pengajuan' => $pengajuan,
            'filters' => $request->only(['search', 'status', 'tipe', 'laporan', 'per_page']),
            'activeTab' => 'all-requests'
        ]);
    }

    /**
     * Create Page.
     */
    public function create(Request $request)
    {
        $karyawan = Auth::user()->karyawan()->with('departemen')->first();
        if (!$karyawan) abort(403, 'User tidak terhubung ke data Karyawan.');

        $prefill = [
            'type'       => $request->type,
            'amount'     => $request->amount,
            'desc'       => $request->desc,
            'account_id' => $request->account_id,
        ];

        return Inertia::render('Admin/Pengajuan/Create', array_merge(
            $this->getMasterData(),
            ['karyawan' => $karyawan, 'prefill' => $prefill]
        ));
    }

    /**
     * Store Logic (Unified).
     */

    public function store(StorePengajuanRequest $request)
    {
        try {
            $result = $this->pengajuanService->create($request->validated(), $request->file('attachment'));
            
            $warnings = $result['warnings'] ?? [];
            
            return redirect()->route('admin.pengajuan.index')
                ->with('success', 'Pengajuan berhasil dibuat.' . (count($warnings) ? ' (Warning: '.implode(', ', $warnings).')' : ''));

        } catch (\Exception $e) {
            Log::error('Gagal simpan: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Show Detail Page.
     */
    public function show(PengajuanHeader $pengajuan)
    {
        // ... previous loads ...
        $pengajuan->load([
            'pengaju:id,nama_lengkap', 
            'departemen:id,nama_departemen', 
            'vendorPenerima:id,nama_vendor',
            'karyawanPenerima:id,nama_lengkap', 
            'detail.akunGl:id,kode_akun,nama_akun', 
            'detail.programKerja:id,nama_program', 
            'detail.pajak:id,kode_pajak', 
            'approvalProcess.targetKaryawan:id,nama_lengkap,jabatan',
            'approvalProcess.actionKaryawan:id,nama_lengkap,jabatan',
            'pembayaran.kasBank', 
            'pembayaran.pembuat:id,name',
            'detail.pajak', // Added eager load
            'approvalProcess.approver',
            'laporanPenggunaan.detail.programKerja',
            'laporanPenggunaan.detail.akunGl',
            'laporanPenggunaan.pelapor',
            // 'activities.causer' // Removed simple load, replaced by manual query below
        ]);

        // --- Audit Log Aggregation (Header + Details + Payments + Laporan) ---
        $activityQuery = \Spatie\Activitylog\Models\Activity::with('causer')
            ->where(function ($query) use ($pengajuan) {
                $query->where('subject_type', 'App\Models\PengajuanHeader')
                      ->where('subject_id', $pengajuan->id);
            });

        // 1. Logs for Details (Items)
        $detailIds = $pengajuan->detail->pluck('id');
        if ($detailIds->isNotEmpty()) {
            $activityQuery->orWhere(function ($query) use ($detailIds) {
                $query->where('subject_type', 'App\Models\PengajuanDetail')
                      ->whereIn('subject_id', $detailIds);
            });
        }

        // 2. Logs for Payments
        $pembayaranIds = $pengajuan->pembayaran->pluck('id');
        if ($pembayaranIds->isNotEmpty()) {
            $activityQuery->orWhere(function ($query) use ($pembayaranIds) {
                $query->where('subject_type', 'App\Models\PengajuanPembayaran')
                      ->whereIn('subject_id', $pembayaranIds);
            });
        }

        // 3. Logs for Settlement (Laporan & Detail Laporan)
        if ($pengajuan->laporanPenggunaan) {
            $laporanId = $pengajuan->laporanPenggunaan->id;
            $activityQuery->orWhere(function ($query) use ($laporanId) {
                $query->where('subject_type', 'App\Models\LaporanPenggunaan')
                      ->where('subject_id', $laporanId);
            });

            // Laporan Details (fetch IDs manually to be safe)
            $laporanDetailIds = \App\Models\LaporanDetail::where('id_laporan', $laporanId)->pluck('id');
            if ($laporanDetailIds->isNotEmpty()) {
                $activityQuery->orWhere(function ($query) use ($laporanDetailIds) {
                    $query->where('subject_type', 'App\Models\LaporanDetail')
                          ->whereIn('subject_id', $laporanDetailIds);
                });
            }
        }

        $activities = $activityQuery->orderBy('created_at', 'desc')->get();
        $pengajuan->setRelation('activities', $activities);
        // ---------------------------------------------------------------------

        $pengajuan->append(['total_dibayar', 'sisa_tagihan', 'status_pembayaran']);

        $user        = Auth::user();
        $permissions = $this->resolveShowPermissions($pengajuan, $user);

        return Inertia::render('Admin/Pengajuan/Show', array_merge(
            ['pengajuan' => $pengajuan],
            $permissions,
            [
                'pin_session_valid' => session('approval_pin_verified_at')
                    && \Carbon\Carbon::parse(session('approval_pin_verified_at'))->diffInMinutes(now()) <= 5,
            ]
        ));
    }

    /**
     * Hitung semua flag permission untuk halaman Show.
     * Memisahkan business-rule logic dari rendering.
     */
    private function resolveShowPermissions(PengajuanHeader $pengajuan, $user): array
    {
        $canApprove = false;
        if ($pengajuan->status_global === \App\Enums\PengajuanStatus::PENDING_APPROVAL) {
            $currentStep = $pengajuan->approvalProcess
                ->where('status', 'Pending')
                ->sortBy('level_order')
                ->first();

            if ($currentStep && $user->karyawan
                && $currentStep->id_karyawan_target == $user->karyawan->id) {
                $canApprove = true;
            }
        }

        $canPay = $user->hasRole(Role::financeRoles())
            && $pengajuan->status_global === \App\Enums\PengajuanStatus::APPROVED
            && $pengajuan->sisa_tagihan > 0;

        $listKasBank = $canPay ? KasBank::where('is_active', true)->get() : [];

        $isUangMuka = $pengajuan->tipe_pengajuan === 'UangMuka';
        $paidOrRevision = in_array($pengajuan->status_global, [
            \App\Enums\PengajuanStatus::PAID,
            \App\Enums\PengajuanStatus::REVISION,
        ]);
        $targetId   = $pengajuan->id_karyawan_penerima ?? $pengajuan->id_pengaju;
        $isTarget   = $user->karyawan && $user->karyawan->id == $targetId;

        $canSettle      = $isUangMuka && $paidOrRevision && ($isTarget || $user->hasRole(Role::SUPER_ADMIN->value));
        $canEditSettlement = $pengajuan->status_global === \App\Enums\PengajuanStatus::REVISION && $canSettle;

        $canVerifySettlement = $user->hasRole(Role::financeRoles())
            && $pengajuan->status_global === \App\Enums\PengajuanStatus::VERIFICATION;

        return compact(
            'canApprove', 'canPay', 'canSettle',
            'canEditSettlement', 'canVerifySettlement', 'listKasBank'
        );
    }

    /**
     * Edit Page.
     */

    public function edit(PengajuanHeader $pengajuan)
    {
        if (!in_array($pengajuan->status_global, [
            PengajuanStatus::PENDING_APPROVAL,
            PengajuanStatus::DRAFT,
            PengajuanStatus::REVISION
        ])) {
            return redirect()->route('admin.pengajuan.show', $pengajuan->id)
                ->with('error', 'Pengajuan ini tidak dapat diedit karena sudah diproses (Status: ' . ($pengajuan->status_global instanceof PengajuanStatus ? $pengajuan->status_global->label() : $pengajuan->status_global) . ').');
        }

        $pengajuan->load('detail');
        $karyawan = Auth::user()->karyawan()->with('departemen')->first();

        return Inertia::render('Admin/Pengajuan/Edit', array_merge(
            $this->getMasterData(),
            ['pengajuan' => $pengajuan, 'karyawan' => $karyawan]
        ));
    }

    /**
     * Update Logic.
     */

    public function update(UpdatePengajuanRequest $request, PengajuanHeader $pengajuan)
    {
        // Allow updating for Pending, Draft, or Revision
        if (!in_array($pengajuan->status_global, [
            PengajuanStatus::PENDING_APPROVAL, 
            PengajuanStatus::DRAFT, 
            PengajuanStatus::REVISION
        ])) {
             return redirect()->route('admin.pengajuan.show', $pengajuan->id)->with('error', 'Tidak bisa diedit.');
        }

        // Simpan flag SEBELUM update() mengubah status_global
        $needsReset = in_array($pengajuan->status_global, [PengajuanStatus::REVISION, PengajuanStatus::PENDING_APPROVAL]);

        try {
            $result = $this->pengajuanService->update($pengajuan, $request->validated(), $request->file('attachment'));

            // Jika status sebelumnya REVISION atau PENDING_APPROVAL → reset approval process dan mulai ulang
            if ($needsReset) {
                // 1. Hapus SEMUA step lama karena dokumen diubah secara fisik, butuh persetujuan ulang
                $pengajuan->approvalProcess()->delete();

                // 2. Re-init approval flow dari awal
                $this->approvalService->initApproval($pengajuan->fresh());
            }

            $warnings = $result['warnings'] ?? [];

            return redirect()->route('admin.pengajuan.index')
                ->with('success', 'Pengajuan berhasil diperbarui.' . (count($warnings) ? ' (Warning: '.implode(', ', $warnings).')' : ''));

        } catch (\Exception $e) {
            Log::error('Gagal update: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }



    /**
     * Delete Logic.
     */

    public function destroy(PengajuanHeader $pengajuan)
    {
        if (!in_array($pengajuan->status_global, [
            PengajuanStatus::PENDING_APPROVAL,
            PengajuanStatus::DRAFT
        ])) {
            return redirect()->back()->with('error', 'Hanya pengajuan Draft/Pending yang bisa dihapus.');
        }

        try {
            $this->pengajuanService->destroyPengajuan($pengajuan);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }

        return redirect()->route('admin.pengajuan.index')->with('success', 'Pengajuan dihapus permanen.');
    }

    /**
     * Cancel Logic (Void).
     * Set status to Cancelled, return Budget, but Keep Record.
     */
    public function cancel(Request $request, PengajuanHeader $pengajuan)
    {
        if ($pengajuan->status_global === PengajuanStatus::PAID || $pengajuan->status_global === PengajuanStatus::SETTLED) {
            return redirect()->back()->with('error', 'Pengajuan sudah dibayar, tidak bisa dibatalkan.');
        }

        try {
            $this->pengajuanService->cancelPengajuan(
                $pengajuan,
                $request->input('reason', ''),
                Auth::id()
            );
            return redirect()->back()->with('success', 'Pengajuan berhasil dibatalkan (Budget dikembalikan).');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membatalkan: ' . $e->getMessage());
        }
    }

    public function toggleOpenCoa(PengajuanHeader $pengajuan)
    {
        if (!Auth::user()->hasRole(Role::financeRoles())) {
            return redirect()->back()->with('error', 'Hanya tim Finance yang dapat membuka akses COA.');
        }

        $pengajuan->update([
            'is_open_coa' => !$pengajuan->is_open_coa
        ]);

        $status = $pengajuan->is_open_coa ? 'dibuka' : 'dikunci';
        return redirect()->back()->with('success', "Akses semua COA untuk pengajuan ini berhasil $status.");
    }

    // =========================================================================
    // PRIVATE: Master Data Helper
    // =========================================================================

    /**
     * Load semua master data yang dibutuhkan form Create dan Edit.
     * Sebelumnya diduplikasi di kedua method — sekarang dipanggil sekali.
     */
    private function getMasterData(): array
    {
        return [
            'masterAkun' => AkunGl::whereIn('tipe_akun', ['Biaya', 'Biaya Modal', 'Aset'])
                ->where('is_active', true)
                ->orderBy('kode_akun')
                ->get(['id', 'nama_akun', 'kode_akun']),

            'masterProgram' => ProgramKerja::orderBy('nama_program')
                ->get(['id', 'nama_program']),

            'masterPajak' => TaxType::where('is_active', true)
                ->whereIn('transaction_type', ['purchase', 'both'])
                ->orderBy('kode_pajak')
                ->get(['id', 'kode_pajak', 'nama_pajak', 'rate', 'tipe']),

            'masterVendor' => Vendor::with('rekeningBank')
                ->where('is_active', 1)
                ->orderBy('nama_vendor')
                ->get()
                ->each(fn($v) => $v->append('primary_bank')),

            'masterKaryawan' => Karyawan::with('rekeningBank')
                ->orderBy('nama_lengkap')
                ->get()
                ->each(fn($k) => $k->append('primary_bank')),

            // Kas Kecil: KasBank yang namanya mengandung 'Kas Kecil', disertai saldo GL real-time
            'masterKasKecil' => KasBank::where('is_active', true)
                ->where('nama_bank', 'like', '%Kas Kecil%')
                ->orderBy('nama_bank')
                ->get(['id', 'nama_bank', 'nomor_rekening', 'id_akun_gl'])
                ->map(function ($kb) {
                    // Hitung saldo GL real-time
                    $saldo = 0;
                    if ($kb->id_akun_gl) {
                        $debit  = \App\Models\JurnalDetail::where('id_akun', $kb->id_akun_gl)->sum('debit');
                        $kredit = \App\Models\JurnalDetail::where('id_akun', $kb->id_akun_gl)->sum('kredit');
                        $saldo  = $debit - $kredit;
                    }
                    return array_merge($kb->toArray(), ['saldo_gl' => $saldo]);
                }),
        ];
    }
}