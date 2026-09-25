<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanCuti;
use App\Models\JenisCuti;
use App\Models\SaldoCuti;
use App\Models\Karyawan;
use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class CutiController extends Controller
{
    protected $approvalService;

    public function __construct(\App\Services\ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    // 1. My Requests (Cuti Saya)
    public function myRequests(Request $request)
    {
        $user = auth()->user();
        $karyawan = Karyawan::where('user_id', $user->id)->first();

        $myRequests = [];
        $balances = [];
        $jenisCutiList = [];

        if ($karyawan) {
            // Load approval process for status tracking
            $myRequests = PengajuanCuti::with(['jenisCuti', 'approvalProcess.targetKaryawan'])
                            ->where('id_karyawan', $karyawan->id)
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);

            $currentYear = date('Y');
            $balances = SaldoCuti::where('id_karyawan', $karyawan->id)
                            ->where('tahun_periode', $currentYear)
                            ->with('jenisCuti')
                            ->get();
            
            $jenisCutiList = JenisCuti::all();
        }

        return Inertia::render('Admin/Cuti/Index', [
            'myRequests' => $myRequests,
            'balances' => $balances,
            'jenisCutiList' => $jenisCutiList,
            'isEmployee' => !!$karyawan,
        ]);
    }

    // 2. Approval Requests (Persetujuan Cuti)
    public function approvals(Request $request)
    {
        $user = auth()->user();
        $karyawan = Karyawan::where('user_id', $user->id)->first();
        
        if (!$karyawan) abort(403, 'Akses ditolak.');

        // Logic: Cari pengajuan cuti di mana user ini adalah "target_karyawan" di step yang sedang "Pending"
        // Logic: Cari pengajuan cuti di mana user ini adalah "target_karyawan"
        $view = $request->input('view', 'pending');
        
        $query = PengajuanCuti::with(['karyawan.departemen', 'jenisCuti', 'approvalProcess']);
        
        if ($view === 'history') {
             $query->whereHas('approvalProcess', function($q) use ($karyawan) {
                $q->where('id_karyawan_target', $karyawan->id)
                  ->whereIn('status', ['Approved', 'Rejected']);
            });
        } else {
             $query->whereHas('approvalProcess', function($q) use ($karyawan) {
                $q->where('id_karyawan_target', $karyawan->id)
                  ->where('status', 'Pending');
            });
        }

        $approvalRequests = $query->orderBy('created_at', $view === 'history' ? 'desc' : 'asc')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Cuti/Approvals', [
            'approvalRequests' => $approvalRequests,
            'approvalView' => $view, // Pass current view state
            'pin_session_valid' => session('approval_pin_verified_at') && \Carbon\Carbon::parse(session('approval_pin_verified_at'))->diffInMinutes(now()) <= 5,
        ]);
    }

    // 3. Management (Manajemen Cuti - HR Only)
    public function management(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole(Role::hrRoles())) {
            abort(403, 'Akses ditolak.');
        }

        $year = $request->year ?? date('Y');
        $search = $request->search;
        $activeTab = $request->tab ?? 'balances'; // balances | history

        // A. Balances Data (Grouped by Employee)
        $employeeQuery = Karyawan::with(['departemen', 'saldoCuti' => function ($q) use ($year) {
            $q->where('tahun_periode', $year)->with('jenisCuti');
        }]);

        if ($search && $activeTab === 'balances') {
            $employeeQuery->where('nama_lengkap', 'like', '%' . $search . '%');
        }

        // Hanya tampilkan karyawan yang memiliki saldo cuti di tahun tersebut
        $employeeQuery->whereHas('saldoCuti', function($q) use ($year) {
            $q->where('tahun_periode', $year);
        });

        $balancesList = $employeeQuery->orderBy('nama_lengkap')->paginate(12, ['*'], 'balances_page')->withQueryString();

        // B. All Requests History (Read Only)
        $historyQuery = PengajuanCuti::with(['karyawan.departemen', 'jenisCuti', 'approver'])
                        ->orderBy('created_at', 'desc');

        if ($search && $activeTab === 'history') {
            $historyQuery->whereHas('karyawan', function($q) use ($search) {
                $q->where('nama_lengkap', 'like', '%' . $search . '%');
            });
        }

        $allRequests = $historyQuery->paginate(20, ['*'], 'history_page')->withQueryString();
        
        $jenisCutiList = JenisCuti::all();

        // C. Summary Metrics
        $summary = [
            'pending_requests' => PengajuanCuti::where('status', 'Pending')
                                    ->orWhere('status', 'Pending Approval')->count(),
            'approved_this_year' => PengajuanCuti::where('status', 'Approved')
                                    ->whereYear('tgl_mulai', $year)->count(),
            'on_leave_today' => PengajuanCuti::where('status', 'Approved')
                                    ->where('tgl_mulai', '<=', now()->toDateString())
                                    ->where('tgl_selesai', '>=', now()->toDateString())
                                    ->count(),
            'total_balances_issued' => SaldoCuti::where('tahun_periode', $year)->count(),
        ];

        return Inertia::render('Admin/Cuti/Management', [
            'balancesList' => $balancesList,
            'allRequests' => $allRequests,
            'jenisCutiList' => $jenisCutiList,
            'activeTab' => $activeTab,
            'filters' => ['year' => $year, 'search' => $search],
            'summary' => $summary,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_jenis_cuti' => 'required|exists:tbl_jenis_cuti,id',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'alasan' => 'required|string|max:500'
        ]);

        $user = auth()->user();
        $karyawan = Karyawan::where('user_id', $user->id)->first();

        if (!$karyawan) return back()->with('error', 'Data karyawan tidak ditemukan.');

        // 0. ADVANCED VALIDATION
        $jenisCuti = JenisCuti::findOrFail($request->id_jenis_cuti);

        if ($jenisCuti->wajib_lampiran) {
            $request->validate(['lampiran' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048']);
        } else {
            $request->validate(['lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048']);
        }

        // A. Backdate Validation
        if (!$jenisCuti->bisa_mundur) {
            $today = Carbon::today()->format('Y-m-d');
            if ($request->tgl_mulai < $today) {
                return back()->with('error', "Jenis cuti '{$jenisCuti->nama_cuti}' tidak dapat diajukan untuk tanggal lampau (Backdate).");
            }
        }

        // A.2 Gender Validation
        if ($jenisCuti->khusus_perempuan && $karyawan->jenis_kelamin !== 'P') {
            return back()->with('error', "Jenis cuti '{$jenisCuti->nama_cuti}' hanya diperuntukkan bagi karyawan perempuan.");
        }

        // B. Overlap Validation
        $hasOverlap = PengajuanCuti::where('id_karyawan', $karyawan->id)
            ->whereIn('status', ['Pending', 'Pending Approval', 'Approved'])
            ->where(function ($query) use ($request) {
                $query->whereBetween('tgl_mulai', [$request->tgl_mulai, $request->tgl_selesai])
                      ->orWhereBetween('tgl_selesai', [$request->tgl_mulai, $request->tgl_selesai])
                      ->orWhere(function ($q) use ($request) {
                          $q->where('tgl_mulai', '<=', $request->tgl_mulai)
                            ->where('tgl_selesai', '>=', $request->tgl_selesai);
                      });
            })
            ->exists();

        if ($hasOverlap) {
            return back()->with('error', 'Anda sudah memiliki pengajuan cuti (Pending/Approved) pada rentang tanggal tersebut.');
        }

        // 1. SMART DURATION CALCULATION
        $start = Carbon::parse($request->tgl_mulai);
        $end   = Carbon::parse($request->tgl_selesai);
        $days  = 0;

        // ✅ Pre-load semua hari libur dalam rentang tanggal sekaligus — tidak ada N+1
        $holidays = \App\Models\HariLibur::whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->pluck('tanggal')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->flip(); // jadikan map untuk O(1) lookup

        $period = \Carbon\CarbonPeriod::create($start, $end);
        foreach ($period as $date) {
            if ($date->isWeekend()) continue;
            if ($holidays->has($date->toDateString())) continue;
            $days++;
        }

        if ($days <= 0) {
            return back()->with('error', 'Durasi cuti 0 hari (mungkin tanggal yang dipilih adalah hari libur/weekend).');
        }

        // 2. CHECK & RESERVE BALANCE
        $currentYear = date('Y');
        $saldo = null;
        
        if (!$jenisCuti->is_unlimited) {
            $saldo = SaldoCuti::where([
                'id_karyawan'   => $karyawan->id,
                'id_jenis_cuti' => $request->id_jenis_cuti,
                'tahun_periode' => $currentYear,
            ])->first();

            if (!$saldo) {
                return back()->with('error', "Saldo cuti belum dibuat oleh HR untuk jenis cuti '{$jenisCuti->nama_cuti}'. Silakan hubungi HR.");
            }

            // Check if balance is sufficient
            $available = $saldo->saldo_awal - $saldo->saldo_terpakai;

            if ($available < $days) {
                return back()->with('error', "Saldo cuti tidak mencukupi. Sisa: $available hari, Diajukan: $days hari.");
            }
        }

        DB::transaction(function () use ($request, $karyawan, $days, $saldo, $jenisCuti) {
            // Upload attachment if any
            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $lampiranPath = $request->file('lampiran')->store('cuti_lampiran', 'public');
            }

            // Create Request
            $cuti = PengajuanCuti::create([
                'id_karyawan' => $karyawan->id,
                'id_jenis_cuti' => $request->id_jenis_cuti,
                'tgl_mulai' => $request->tgl_mulai,
                'tgl_selesai' => $request->tgl_selesai,
                'jumlah_hari' => $days, // Effective days
                'alasan' => $request->alasan,
                'status' => 'Pending',
                'lampiran_path' => $lampiranPath
            ]);

            // RESERVE BALANCE (Deduct Immediately)
            if ($saldo) {
                if (!$jenisCuti->is_unlimited) {
                    $saldo->increment('saldo_terpakai', $days);
                    $saldo->decrement('saldo_akhir', $days); // Maintain legacy column
                } else {
                    // Untuk cuti unlimited (Sakit, dll), kita tetap hitung 'saldo_terpakai'
                    // sebagai tracking jumlah hari yang diambil, tanpa memotong saldo_akhir
                    $saldo->increment('saldo_terpakai', $days);
                }
            }

            // Init Approval Workflow
            $this->approvalService->initApproval($cuti);
        });

        return redirect()->route('admin.cuti.my-requests')->with('success', "Pengajuan cuti berhasil dikirim. Durasi efektif: $days hari (Weekend/Libur dilewati).");
    }
    
    public function approve(Request $request, PengajuanCuti $cuti)
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected',
            'catatan' => 'nullable|string'
        ]);

        $user = auth()->user();
        $karyawan = $user->karyawan;

        if (!$karyawan) return back()->with('error', 'Akses ditolak.');

        try {
            if ($request->status === 'Approved') {
                $this->approvalService->approve($cuti, $karyawan->id, $request->catatan);
                $message = 'Pengajuan cuti berhasil disetujui.';
            } else {
                // REFUND BALANCE IF REJECTED
                // Since we reserved (deducted/incremented) it at Store, we must give it back now.
                $currentYear = date('Y', strtotime($cuti->tgl_mulai)); // Use leave year
                $saldo = SaldoCuti::where('id_karyawan', $cuti->id_karyawan)
                    ->where('id_jenis_cuti', $cuti->id_jenis_cuti)
                    ->where('tahun_periode', $currentYear)
                    ->first();

                if ($saldo) {
                    if (!$cuti->jenisCuti->is_unlimited) {
                        $saldo->decrement('saldo_terpakai', $cuti->jumlah_hari);
                        $saldo->increment('saldo_akhir', $cuti->jumlah_hari);
                    } else {
                        // Untuk unlimited, kita hanya mengurangi saldo_terpakai
                        $saldo->decrement('saldo_terpakai', $cuti->jumlah_hari);
                    }
                }

                $this->approvalService->reject($cuti, $karyawan->id, $request->catatan);
                $message = 'Pengajuan cuti berhasil ditolak dan saldo dikembalikan.';
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses approval: ' . $e->getMessage());
        }
    }

    public function generate(Request $request)
    {
        $request->validate([
            'tahun'          => 'required|integer|min:2020|max:2030',
            'id_jenis_cuti'  => 'required|exists:tbl_jenis_cuti,id'
        ]);

        $jenisCuti  = JenisCuti::findOrFail($request->id_jenis_cuti);
        $tahun      = (int) $request->tahun;

        // ✅ Load semua karyawan aktif sekaligus
        $karyawans  = Karyawan::where('status_karyawan', '!=', 'Resign')
            ->get(['id', 'jenis_kelamin']);

        // ✅ Load existing saldo untuk periode ini sekaligus — 1 query
        $existingIds = SaldoCuti::where('id_jenis_cuti', $request->id_jenis_cuti)
            ->where('tahun_periode', $tahun)
            ->pluck('id_karyawan')
            ->flip(); // O(1) lookup

        // ✅ Siapkan bulk insert — hanya untuk yang belum ada
        $toInsert = [];
        $now      = now();

        foreach ($karyawans as $karyawan) {
            if ($existingIds->has($karyawan->id)) continue; // sudah ada, skip

            $quota = $jenisCuti->kuota_default;
            if ($jenisCuti->khusus_perempuan && $karyawan->jenis_kelamin !== 'P') {
                $quota = 0;
            }

            $toInsert[] = [
                'id_karyawan'   => $karyawan->id,
                'id_jenis_cuti' => $jenisCuti->id,
                'tahun_periode' => $tahun,
                'saldo_awal'    => $quota,
                'saldo_terpakai'=> 0,
                'saldo_akhir'   => $quota,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        // ✅ Insert sekaligus — 1 query (bukan N query)
        $count = 0;
        if (!empty($toInsert)) {
            SaldoCuti::insert($toInsert);
            $count = count($toInsert);
        }

        return back()->with('success', "Berhasil generate saldo cuti untuk $count karyawan.");
    }

    public function updateBalance(Request $request, $id)
    {
        $request->validate([
            'saldo_awal' => 'required|integer|min:0',
            'saldo_terpakai' => 'required|integer|min:0'
        ]);

        $saldo = SaldoCuti::findOrFail($id);
        $saldo->update([
            'saldo_awal' => $request->saldo_awal,
            'saldo_terpakai' => $request->saldo_terpakai,
            'saldo_akhir' => $request->saldo_awal - $request->saldo_terpakai
        ]);

        return back()->with('success', 'Saldo cuti berhasil diperbarui.');
    }

    public function show(PengajuanCuti $cuti)
    {
        $cuti->load(['karyawan.departemen', 'jenisCuti', 'approvalProcess.targetKaryawan']);
        
        $qrPemohon = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(100)->generate(route('public.verify', 'CUTI-' . encrypt($cuti->id))));

        foreach ($cuti->approvalProcess as $step) {
            if (in_array($step->status, ['Approved', 'Rejected'])) {
                $step->setAttribute('qr_base64', base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(100)->generate(route('public.verify', $step->uuid))));
            }
        }

        return inertia('Admin/Cuti/Show', [
            'cuti' => $cuti,
            'qrPemohon' => $qrPemohon
        ]);
    }

    public function print(PengajuanCuti $cuti)
    {
        $cuti->load(['karyawan.departemen', 'jenisCuti', 'approvalProcess.targetKaryawan']);
        
        return view('admin.cuti.print', compact('cuti'));
    }

    public function exportPdf(PengajuanCuti $cuti)
    {
        $cuti->load(['karyawan.departemen', 'jenisCuti', 'approvalProcess.targetKaryawan']);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.cuti.print', compact('cuti'));
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download("Cuti_{$cuti->karyawan->nama_lengkap}_{$cuti->id}.pdf");
    }
}
