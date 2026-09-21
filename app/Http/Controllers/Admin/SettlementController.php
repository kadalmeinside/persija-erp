<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanHeader;
use App\Models\LaporanPenggunaan;
use App\Models\LaporanDetail;
use App\Models\ProgramKerja; 
use App\Models\AkunGl; 
use App\Models\BudgetMaster; 
use App\Models\PeriodeAnggaran; 
use App\Services\BudgetCheckService; 
use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Tambahkan Log
use Inertia\Inertia;
use Carbon\Carbon; 
use iio\libmergepdf\Merger;
use Illuminate\Support\Facades\Response;

class SettlementController extends Controller
{
    protected $budgetCheckService;

    // Inject BudgetCheckService
    public function __construct(BudgetCheckService $budgetCheckService)
    {
        $this->budgetCheckService = $budgetCheckService;
    }

    /**
     * Helper untuk mendapatkan periode aktif
     */
    private function getActivePeriodeId()
    {
        $periode = PeriodeAnggaran::where('is_active', true)->first();
        if (!$periode) {
            $now = Carbon::now();
            $periode = PeriodeAnggaran::whereDate('tanggal_mulai', '<=', $now)
                        ->whereDate('tanggal_selesai', '>=', $now)
                        ->first();
        }
        return $periode ? $periode->id : null;
    }

    public function create(PengajuanHeader $pengajuan)
    {
        // 1. Validasi Status & Tipe
        // Status bisa Paid atau Revision (jika user revisi)
        $statusValue = $pengajuan->status_global->value ?? $pengajuan->status_global;
        if ($pengajuan->tipe_pengajuan !== 'UangMuka' || !in_array($statusValue, ['Paid', 'Revision'])) {
            return redirect()->back()->with('error', 'Pengajuan ini tidak bisa dibuat laporan pertanggungjawaban.');
        }

        // 2. Validasi Hak Akses (User Terkait)
        $user = Auth::user();
        // Tentukan siapa yang berhak: Karyawan Penerima (jika ada) atau Pengaju Awal
        $targetKaryawanId = $pengajuan->id_karyawan_penerima ?? $pengajuan->id_pengaju;

        // Cek: Apakah user punya data karyawan DAN ID-nya cocok? (Atau dia Super Admin)
        $isAuthorized = ($user->karyawan && $user->karyawan->id == $targetKaryawanId) || $user->hasRole(Role::SUPER_ADMIN->value);

        if (!$isAuthorized) {
            return redirect()->back()->with('error', 'Akses Ditolak: Laporan pertanggungjawaban hanya dapat dibuat oleh penerima dana yang bersangkutan.');
        }

        // Load detail estimasi awal & Data Orang
        $pengajuan->load([
            'detail.programKerja', 
            'detail.akunGl',
            'karyawanPenerima', // Load relasi penerima
            'pengaju'           // Load relasi pengaju
        ]);

        // Ambil Periode Aktif
        $periodeId = $this->getActivePeriodeId();

        // 3. Ambil Master Program Kerja & Akun GL (Strict vs Open COA)
        if ($pengajuan->is_open_coa) {
            $masterProgram = ProgramKerja::orderBy('nama_program')->get(['id', 'nama_program']);
            $masterAkun = AkunGl::whereIn('tipe_akun', ['Biaya', 'Biaya Modal', 'Aset'])
                ->where('is_active', true)
                ->orderBy('kode_akun')
                ->get(['id', 'nama_akun', 'kode_akun']);
        } else {
            $masterProgram = $pengajuan->detail->pluck('programKerja')->unique('id')->filter()->values();
            $masterAkun = $pengajuan->detail->pluck('akunGl')->unique('id')->filter()->values();
        }

        return Inertia::render('Admin/Settlement/Create', [
            'pengajuan' => $pengajuan,
            'penerima' => $pengajuan->karyawanPenerima ?? $pengajuan->pengaju,
            'masterProgram' => $masterProgram, 
            'masterAkun' => $masterAkun,       
        ]);
    }

    public function store(Request $request, PengajuanHeader $pengajuan)
    {
        // Validasi Hak Akses (Security Layer di Backend)
        $user = Auth::user();
        $targetKaryawanId = $pengajuan->id_karyawan_penerima ?? $pengajuan->id_pengaju;
        $isAuthorized = ($user->karyawan && $user->karyawan->id == $targetKaryawanId) || $user->hasRole(Role::SUPER_ADMIN->value);

        if (!$isAuthorized) {
            return redirect()->back()->with('error', 'Akses Ditolak: Anda tidak berhak menyimpan laporan ini.');
        }

        $validator = $request->validate([
            'tgl_laporan' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.deskripsi_bon' => 'required|string',
            'items.*.nominal_bon' => 'required|numeric|min:0',
            // Validasi tambahan: Pastikan program/akun yang dipilih valid
            'items.*.id_program' => 'required|integer|exists:tbl_program_kerja,id', 
            'items.*.id_akun' => 'required|integer|exists:tbl_akun_gl,id',
            'items.*.file_bukti' => 'required|file|mimes:jpg,png,pdf|max:2048', 
            'bukti_pengembalian' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
        ]);

        try {
            DB::transaction(function () use ($request, $pengajuan) {
                
                // 1. UN-COMMIT ANGGARAN LAMA (PENTING!)
                // Kembalikan saldo ke periode asal pengajuan (misal: Januari)
                $oldDate = $pengajuan->tgl_pengajuan; 
                foreach ($pengajuan->detail as $oldItem) {
                    $budgetId = $this->budgetCheckService->getBudgetId(
                        $pengajuan->id_departemen, 
                        $oldItem->id_akun, 
                        $oldItem->id_program, 
                        $oldDate
                    );
                    
                    if ($budgetId) {
                        $this->budgetCheckService->unCommitBudget($budgetId, $oldItem->nominal_item);
                    }
                }

                // 2. Hitung Total Realisasi & Upload Bukti Pengembalian
                $totalRealisasi = collect($request->items)->sum('nominal_bon');
                $uangMukaAwal = $pengajuan->total_nominal_diajukan;
                $selisih = $uangMukaAwal - $totalRealisasi; 

                $pathBuktiKembali = null;
                if ($request->hasFile('bukti_pengembalian')) {
                    $pathBuktiKembali = $request->file('bukti_pengembalian')->store('settlements/refund', 'public');
                }

                // 3. Simpan Header Laporan
                $laporan = LaporanPenggunaan::create([
                    'id_pengajuan_uam' => $pengajuan->id,
                    'id_pelapor' => Auth::user()->karyawan->id ?? 0,
                    'tgl_laporan' => $request->tgl_laporan,
                    'total_realisasi_aktual' => $totalRealisasi,
                    'selisih' => $selisih,
                    'bukti_pengembalian_path' => $pathBuktiKembali
                ]);

                // 4. Simpan Detail & COMMIT ANGGARAN BARU (REALISASI)
                
                // PERBAIKAN UTAMA: Gunakan Tanggal Pengajuan Asal untuk Cek & Commit Budget
                // Agar kita menggunakan "slot" anggaran yang sama dengan yang baru saja kita kembalikan (Un-commit).
                $tglBudget = $pengajuan->tgl_pengajuan; 

                foreach ($request->items as $index => $item) {
                    
                    // A. CEK & POTONG BUDGET REALISASI (Gunakan $tglBudget)
                    $budgetCheck = $this->budgetCheckService->check(
                        $pengajuan->id_departemen,
                        $item['id_akun'],
                        $item['id_program'],
                        $item['nominal_bon'],
                        $tglBudget // <-- Menggunakan tanggal asal
                    );

                    if (!$budgetCheck['success']) {
                        throw new \Exception("Budget Tidak Cukup untuk Item '{$item['deskripsi_bon']}': " . $budgetCheck['message']);
                    }
                    
                    // B. Upload Bukti Item
                    $pathBuktiItem = null;
                    if (isset($item['file_bukti']) && $item['file_bukti'] instanceof \Illuminate\Http\UploadedFile) {
                        $pathBuktiItem = $item['file_bukti']->store('settlements/receipts', 'public');
                    }

                    // C. Simpan DB
                    LaporanDetail::create([
                        'id_laporan' => $laporan->id,
                        'deskripsi_bon' => $item['deskripsi_bon'],
                        'nominal_bon' => $item['nominal_bon'],
                        'id_departemen_beban' => $pengajuan->id_departemen, 
                        'id_program_beban' => $item['id_program'],
                        'id_akun_beban' => $item['id_akun'],
                        'bukti_path' => $pathBuktiItem, 
                    ]);

                    // D. Commit Budget (Realisasi)
                    // Saat ini budget kita "Commit" (Booking) dulu. Nanti saat verify baru dipindah ke Realisasi.
                    $this->budgetCheckService->commitBudget($budgetCheck['budget_id'], $item['nominal_bon']);
                }

                // 5. UPDATE STATUS: VERIFICATION (Mengirim ke Finance)
                $pengajuan->update(['status_global' => 'Verification']);
            });

        } catch (\Exception $e) {
            Log::error("Settlement Error: " . $e->getMessage()); // Log error untuk debugging
            return redirect()->back()->with('error', 'Gagal simpan laporan: ' . $e->getMessage());
        }

        return redirect()->route('admin.pengajuan.show', $pengajuan->id)->with('success', 'Laporan berhasil dikirim ke Finance untuk diverifikasi.');
    }

    /**
     * ACTION: Finance Verifikasi Laporan -> Settled (Lunas/Selesai)
     */
    public function verify(Request $request, PengajuanHeader $pengajuan)
    {
        // 1. Validasi Role Finance
        if (!Auth::user()->hasRole(Role::financeRoles())) {
            return redirect()->back()->with('error', 'Akses Ditolak. Hanya Finance yang bisa memverifikasi.');
        }
        
        // 2. Validasi Status
        $statusValue = $pengajuan->status_global->value ?? $pengajuan->status_global;
        if ($statusValue !== 'Verification') {
            return redirect()->back()->with('error', 'Status pengajuan tidak valid untuk diverifikasi.');
        }

        $isReapproval = false;

        DB::transaction(function () use ($pengajuan, &$isReapproval) {
            $laporan = $pengajuan->laporanPenggunaan;
            
            if ($laporan) {
                // UPDATE VERIFICATION DETAILS
                $laporan->update([
                    'id_verifier' => Auth::user()->karyawan->id ?? null,
                    'verified_at' => now(),
                    'verify_uuid' => (string) \Illuminate\Support\Str::uuid()
                ]);

                // 3. Pindahkan Anggaran dari Terikat (Commitment) ke Realisasi (Actual)
                $tglBudget = $pengajuan->tgl_pengajuan; 
                foreach ($laporan->detail as $item) {
                    $budgetId = $this->budgetCheckService->getBudgetId(
                        $item->id_departemen_beban, 
                        $item->id_akun_beban, 
                        $item->id_program_beban, 
                        $tglBudget
                    );
                    if ($budgetId) {
                        $this->budgetCheckService->realizeBudget($budgetId, $item->nominal_bon);
                    }
                }

                // 4. Cek Selisih (Kurang Bayar vs Lebih Bayar/Impas)
                // Selisih di DB: (Uang Muka - Realisasi). 
                // Jika Negatif = Kurang Bayar (Reimburse).
                // Jika Positif = Lebih Bayar (Refund).
                
                if ($laporan->selisih < 0) {
                    // KASUS KURANG BAYAR (REIMBURSE)
                    $deficit = abs($laporan->selisih);
                    $tolerance = (float) \App\Models\Setting::where('key', 'reimburse_tolerance_limit')->value('value');
                    // Jika null, fallback ke 1.000.000 (meski di seeder diset)
                    if (!$tolerance && $tolerance !== 0.0) {
                        $tolerance = 1000000; 
                    }

                    if ($deficit <= $tolerance) {
                        // AUTO-APPROVE: Update Total Pengajuan agar sesuai dengan Realisasi (Naik)
                        $pengajuan->update([
                            'total_nominal_diajukan' => $laporan->total_realisasi_aktual,
                            'status_global' => 'Approved' // Kembali ke Approved agar bisa dibayar sisanya
                        ]);
                    } else {
                        // EXCEEDS TOLERANCE: Re-approval Dokumen Lama (Opsi B)
                        // 1. Update Total Pengajuan menjadi sesuai Realisasi
                        $pengajuan->update([
                            'total_nominal_diajukan' => $laporan->total_realisasi_aktual,
                            'status_global' => 'Pending Approval' // Dikembalikan ke pending approval
                        ]);

                        // 2. Hapus log approval lama agar disetujui ulang dari awal
                        $pengajuan->approvalProcess()->delete();

                        // 3. Inisialisasi ulang approval
                        $approvalService = app(\App\Services\ApprovalService::class);
                        $approvalService->initApproval($pengajuan);
                        
                        $isReapproval = true; // Flag untuk custom notifikasi
                    }
                } else {
                    // KASUS IMPAS ATAU LEBIH BAYAR (REFUND)
                    // Asumsi: Jika refund, Finance sudah terima uangnya (manual check).
                    $pengajuan->update(['status_global' => 'Settled']);
                }
            }
        });

        // Redirect message dynamic
        $statusValue = $pengajuan->status_global->value ?? $pengajuan->status_global;
        if ($statusValue === 'Approved') {
            return redirect()->back()->with('success', 'Laporan diverifikasi. Status: Approved (Kurang Bayar). Silakan input pembayaran sisa.');
        } else {
            if ($isReapproval) {
                return redirect()->back()->with('success', 'Laporan diverifikasi. Karena selisih nombok melebihi batas toleransi, dokumen dikembalikan ke Pending Approval untuk disetujui ulang oleh Atasan.');
            }
            return redirect()->back()->with('success', 'Laporan diverifikasi. Status: Settled (Selesai).');
        }
    }

    /**
     * ACTION: Finance Tolak Laporan -> Kembali ke Paid (Revisi)
     */
    public function reject(Request $request, PengajuanHeader $pengajuan)
    {
        // 1. Validasi Role
        if (!Auth::user()->hasRole(Role::financeRoles())) {
            return redirect()->back()->with('error', 'Akses Ditolak.');
        }

        // Validasi input
        $request->validate([
            'tipe_revisi' => 'required|in:full,partial',
            'catatan' => 'required|string|max:500'
        ]);

        // 2. Validasi Status
        $statusValue = $pengajuan->status_global->value ?? $pengajuan->status_global;
        if ($statusValue !== 'Verification') {
            return redirect()->back()->with('error', 'Status pengajuan tidak valid.');
        }

        DB::transaction(function () use ($pengajuan, $request) {
            // 3. Un-Commit Budget Realisasi (Laporan Salah)
            // Kita batalkan booking budget yang dibuat saat Store Laporan
            $laporan = $pengajuan->laporanPenggunaan;
            if ($laporan) {
                $tglBudget = $pengajuan->tgl_pengajuan; 
                foreach ($laporan->detail as $item) {
                    $budgetId = $this->budgetCheckService->getBudgetId(
                        $item->id_departemen_beban, 
                        $item->id_akun_beban, 
                        $item->id_program_beban, 
                        $tglBudget
                    );
                    if ($budgetId) {
                        $this->budgetCheckService->unCommitBudget($budgetId, $item->nominal_bon);
                    }
                }
                
                // Hapus Data Laporan HANYA jika tipe revisi adalah full
                if ($request->tipe_revisi === 'full') {
                    $laporan->detail()->delete();
                    $laporan->delete();
                }
            }

            // 4. Re-Commit Budget Estimasi Awal
            // Kembalikan status budget seolah-olah uang muka baru cair (sebelum laporan dibuat)
            // Agar uangnya tetap "terikat" atas nama user tersebut
            foreach ($pengajuan->detail as $estItem) {
                 $budgetId = $this->budgetCheckService->getBudgetId(
                     $pengajuan->id_departemen, 
                     $estItem->id_akun, 
                     $estItem->id_program, 
                     $pengajuan->tgl_pengajuan
                 );
                 if ($budgetId) {
                     $this->budgetCheckService->commitBudget($budgetId, $estItem->nominal_item);
                 }
            }

            // 5. Update Status ke Revision (Revisi)
            // Agar user bisa edit laporan yang salah
            $pengajuan->update([
                'status_global' => 'Revision',
                'catatan_header' => $request->catatan // Simpan catatan revisi di header (atau buat field khusus jika perlu)
            ]); 
        });

        $message = $request->tipe_revisi === 'full' 
            ? 'Laporan ditolak total. User harus membuat laporan dari nol.' 
            : 'Laporan ditolak parsial. User dapat mengedit data yang sudah ada.';

        return redirect()->back()->with('error', $message);
    }

    /**
     * Edit Page for Settlement (Revision)
     */
    public function edit(PengajuanHeader $pengajuan)
    {
        // 1. Validasi Status
        $statusValue = $pengajuan->status_global->value ?? $pengajuan->status_global;
        if ($statusValue !== 'Revision') {
            return redirect()->route('admin.pengajuan.show', $pengajuan->id)->with('error', 'Laporan tidak dalam status revisi.');
        }

        // 2. Validasi Hak Akses
        $user = Auth::user();
        $targetKaryawanId = $pengajuan->id_karyawan_penerima ?? $pengajuan->id_pengaju;
        $isAuthorized = ($user->karyawan && $user->karyawan->id == $targetKaryawanId) || $user->hasRole(Role::SUPER_ADMIN->value);

        if (!$isAuthorized) {
            return redirect()->back()->with('error', 'Akses Ditolak.');
        }

        // Load data
        $pengajuan->load([
            'detail.programKerja', 
            'detail.akunGl',
            'karyawanPenerima', 
            'pengaju',
            'laporanPenggunaan.detail' // Load detail laporan yang sudah ada
        ]);

        $periodeId = $this->getActivePeriodeId();
        
        if ($pengajuan->is_open_coa) {
            $masterProgram = ProgramKerja::orderBy('nama_program')->get(['id', 'nama_program']);
            $masterAkun = AkunGl::whereIn('tipe_akun', ['Biaya', 'Biaya Modal', 'Aset'])
                ->where('is_active', true)
                ->orderBy('kode_akun')
                ->get(['id', 'nama_akun', 'kode_akun']);
        } else {
            $masterProgram = $pengajuan->detail->pluck('programKerja')->unique('id')->filter()->values();
            $masterAkun = $pengajuan->detail->pluck('akunGl')->unique('id')->filter()->values();
        }

        return Inertia::render('Admin/Settlement/Edit', [
            'pengajuan' => $pengajuan,
            'penerima' => $pengajuan->karyawanPenerima ?? $pengajuan->pengaju,
            'masterProgram' => $masterProgram, 
            'masterAkun' => $masterAkun,       
            'laporan' => $pengajuan->laporanPenggunaan
        ]);
    }

    /**
     * Update Logic for Settlement
     */
    public function update(Request $request, PengajuanHeader $pengajuan)
    {
        // Validasi Hak Akses
        $user = Auth::user();
        $targetKaryawanId = $pengajuan->id_karyawan_penerima ?? $pengajuan->id_pengaju;
        $isAuthorized = ($user->karyawan && $user->karyawan->id == $targetKaryawanId) || $user->hasRole(Role::SUPER_ADMIN->value);

        if (!$isAuthorized) return redirect()->back()->with('error', 'Akses Ditolak.');

        $validator = $request->validate([
            'tgl_laporan' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.deskripsi_bon' => 'required|string',
            'items.*.nominal_bon' => 'required|numeric|min:0',
            'items.*.id_program' => 'required|integer|exists:tbl_program_kerja,id', 
            'items.*.id_akun' => 'required|integer|exists:tbl_akun_gl,id',
            'items.*.file_bukti' => 'nullable', // Bisa file atau string (path lama)
            'bukti_pengembalian' => 'nullable',
        ]);

        try {
            DB::transaction(function () use ($request, $pengajuan) {
                $laporan = $pengajuan->laporanPenggunaan;
                
                // 1. Un-Commit Budget Realisasi LAMA (karena kita akan replace)
                // Note: Saat reject, kita sudah un-commit realisasi dan re-commit estimasi.
                // Jadi status budget saat ini adalah: Estimasi Terikat (Committed).
                // Kita TIDAK PERLU un-commit realisasi disini karena sudah dilakukan saat Reject.
                // TAPI, kita perlu UN-COMMIT ESTIMASI lagi agar bisa diganti dengan Realisasi Baru.
                // (Logic sama persis dengan Store baru)
                
                $oldDate = $pengajuan->tgl_pengajuan; 
                foreach ($pengajuan->detail as $oldItem) {
                    $budgetId = $this->budgetCheckService->getBudgetId($pengajuan->id_departemen, $oldItem->id_akun, $oldItem->id_program, $oldDate);
                    if ($budgetId) $this->budgetCheckService->unCommitBudget($budgetId, $oldItem->nominal_item);
                }

                // 2. Hitung Total & File
                $totalRealisasi = collect($request->items)->sum('nominal_bon');
                $uangMukaAwal = $pengajuan->total_nominal_diajukan;
                $selisih = $uangMukaAwal - $totalRealisasi; 

                $pathBuktiKembali = $laporan->bukti_pengembalian_path;
                if ($request->hasFile('bukti_pengembalian')) {
                    if ($pathBuktiKembali) Storage::disk('public')->delete($pathBuktiKembali);
                    $pathBuktiKembali = $request->file('bukti_pengembalian')->store('settlements/refund', 'public');
                }

                // 3. Update Header Laporan
                $laporan->update([
                    'tgl_laporan' => $request->tgl_laporan,
                    'total_realisasi_aktual' => $totalRealisasi,
                    'selisih' => $selisih,
                    'bukti_pengembalian_path' => $pathBuktiKembali
                ]);

                // 4. Replace Detail
                $laporan->detail()->delete(); // Hapus detail lama
                
                $tglBudget = $pengajuan->tgl_pengajuan; 

                foreach ($request->items as $item) {
                    // A. Cek Budget
                    $budgetCheck = $this->budgetCheckService->check(
                        $pengajuan->id_departemen,
                        $item['id_akun'],
                        $item['id_program'],
                        $item['nominal_bon'],
                        $tglBudget
                    );

                    if (!$budgetCheck['success']) throw new \Exception("Budget Error: " . $budgetCheck['message']);
                    
                    // B. File Bukti Item
                    $pathBuktiItem = $item['existing_path'] ?? null; // Handle existing path from frontend
                    if (isset($item['file_bukti']) && $item['file_bukti'] instanceof \Illuminate\Http\UploadedFile) {
                        $pathBuktiItem = $item['file_bukti']->store('settlements/receipts', 'public');
                    }

                    // C. Create Detail
                    LaporanDetail::create([
                        'id_laporan' => $laporan->id,
                        'deskripsi_bon' => $item['deskripsi_bon'],
                        'nominal_bon' => $item['nominal_bon'],
                        'id_departemen_beban' => $pengajuan->id_departemen, 
                        'id_program_beban' => $item['id_program'],
                        'id_akun_beban' => $item['id_akun'],
                        'bukti_path' => $pathBuktiItem, 
                    ]);

                    // D. Commit Budget (Realisasi)
                    $this->budgetCheckService->commitBudget($budgetCheck['budget_id'], $item['nominal_bon']);
                }

                // 5. Update Status
                $pengajuan->update(['status_global' => 'Verification']);
            });

        } catch (\Exception $e) {
            Log::error("Settlement Update Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal update laporan: ' . $e->getMessage());
        }

        return redirect()->route('admin.pengajuan.show', $pengajuan->id)->with('success', 'Laporan revisi berhasil dikirim.');
    }
    /**
     * Print Laporan Penggunaan PDF
     */
    public function print(PengajuanHeader $pengajuan)
    {
        $pengajuan->load([
            'pengaju', 'departemen', 'karyawanPenerima',
            'laporanPenggunaan.detail.akunGl',
            'laporanPenggunaan.detail.programKerja',
            'laporanPenggunaan.verifier'
        ]);

        if (!$pengajuan->laporanPenggunaan) {
            return redirect()->back()->with('error', 'Laporan belum dibuat.');
        }

        // Fetch Company Settings
        $settings = \App\Models\Setting::all()->pluck('value', 'key');
        
        $logoPath = null;
        if (isset($settings['company_logo']) && $settings['company_logo']) {
            $logoPath = storage_path('app/public/' . $settings['company_logo']);
            if (!file_exists($logoPath)) {
                $logoPath = null; 
            }
        }

        $company = [
            'name' => $settings['company_name'] ?? 'PERSIJA JAYA JAKARTA',
            'address' => $settings['company_address'] ?? 'Rasuna Office Park, Kuningan, Jakarta Selatan',
            'logo_path' => $logoPath,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.settlement.print', [
            'pengajuan' => $pengajuan,
            'laporan' => $pengajuan->laporanPenggunaan,
            'timestamp' => now()->format('d/m/Y H:i'),
            'company' => $company,
        ]);

        $safeName = str_replace(['/', '\\'], '-', $pengajuan->nomor_pengajuan);
        
        $merger = new Merger;
        $merger->addRaw($pdf->output());

        $hasPdf = false;
        foreach ($pengajuan->laporanPenggunaan->detail as $item) {
            if ($item->bukti_path) {
                $absPath = storage_path('app/public/' . $item->bukti_path);
                if (file_exists($absPath)) {
                    $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
                    if ($ext === 'pdf') {
                        $merger->addFile($absPath);
                        $hasPdf = true;
                    }
                }
            }
        }

        if ($hasPdf) {
            try {
                $mergedPdfContent = $merger->merge();
                return Response::make($mergedPdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="Settlement_' . $safeName . '.pdf"'
                ]);
            } catch (\Exception $e) {
                // Fallback
                return $pdf->stream('Settlement_' . $safeName . '.pdf');
            }
        }

        return $pdf->stream('Settlement_' . $safeName . '.pdf');
    }
}
