<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanHeader;
use App\Models\PengajuanDetail;
use App\Models\AkunGl;
use App\Models\ProgramKerja;
use App\Models\Pajak;
use App\Models\Karyawan;
use App\Models\BudgetMaster;
use App\Models\Vendor; // <-- IMPORT BARU
use App\Services\BudgetCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage; // <-- IMPORT BARU
use Inertia\Inertia;
use Carbon\Carbon;

class PengajuanController extends Controller
{
    protected $budgetCheckService;

    public function __construct(BudgetCheckService $budgetCheckService)
    {
        $this->budgetCheckService = $budgetCheckService;
    }

    /**
     * [BARU] Menangani request AJAX untuk cek saldo.
     */
    public function getAccountsByProgram(Request $request)
    {
        // Validasi input
        $request->validate([
            'id_departemen' => 'required|integer',
            'id_program' => 'required|integer',
        ]);

        // Cari di tabel Budget Master:
        // "Ambilkan semua akun GL yang punya anggaran di Tahun ini, untuk Dept ini dan Program ini"
        $akuns = BudgetMaster::where('id_departemen', $request->id_departemen)
                    ->where('id_program', $request->id_program)
                    ->where('tahun', Carbon::now()->year) // Asumsi tahun berjalan
                    ->with('akunGl') // Load relasi ke tabel akun
                    ->get()
                    ->pluck('akunGl') // Ambil data akun-nya saja
                    ->unique('id')    // Pastikan tidak ada duplikat
                    ->values();       // Reset index array

        // Kembalikan dalam format JSON agar bisa dibaca oleh Vue
        return response()->json($akuns);
    }

    /**
     * [AJAX] Mengambil Sisa Saldo Anggaran secara Real-time.
     * Digunakan saat user memilih akun di form Create.
     */
    public function getBudgetBalance(Request $request)
    {
        // Validasi input
        $validator = $request->validate([
            'id_departemen' => 'required|integer',
            'id_akun' => 'required|integer',
            'id_program' => 'required|integer',
            'tgl_pengajuan' => 'required|date',
        ]);

        // Panggil logic perhitungan di Service (agar kodingan rapi)
        // Pastikan Anda sudah membuat method getSisaSaldoDB di BudgetCheckService
        $result = $this->budgetCheckService->getSisaSaldoDB(
            $validator['id_departemen'],
            $validator['id_akun'],
            $validator['id_program'],
            Carbon::parse($validator['tgl_pengajuan'])
        );

        // Kembalikan JSON: { success: true, sisa_saldo_db: 5000000, ... }
        return response()->json($result);
    }


    /**
     * Menampilkan daftar pengajuan.
     */
    public function index()
    {
        $karyawan = Auth::user()->karyawan;
        
        if (!$karyawan) {
            abort(403, 'User Anda tidak terhubung ke data Karyawan. Harap hubungi Admin.');
        }

        $query = PengajuanHeader::with(['pengaju', 'departemen']);

        // Filter berdasarkan role
        if (Auth::user()->hasRole('Staf')) {
            $query->where('id_pengaju', $karyawan->id);
        } elseif (Auth::user()->hasRole('Manajer Departemen')) {
            $query->where('id_departemen', $karyawan->id_departemen);
        }

        $pengajuan = $query->orderBy('tgl_pengajuan', 'desc')->paginate(10);

        return Inertia::render('Admin/Pengajuan/Index', [
            'pengajuan' => $pengajuan,
        ]);
    }

    /**
     * Menampilkan form untuk membuat pengajuan baru.
     */
    public function create()
    {
        $masterAkun = AkunGl::whereIn('tipe_akun', ['Biaya', 'Biaya Modal'])
                            ->orderBy('kode_akun')->get(['id', 'nama_akun', 'kode_akun']);
        
        $masterProgram = ProgramKerja::orderBy('nama_program')->get(['id', 'nama_program']);
        $masterPajak = Pajak::orderBy('kode_pajak')->get(['id', 'kode_pajak']);
        $masterVendor = Vendor::orderBy('nama_vendor')->get(['id', 'nama_vendor']); // <-- BARU
        
        $karyawan = Auth::user()->karyawan()->with('departemen')->first();

        if (!$karyawan) {
            abort(403, 'User Anda tidak terhubung ke data Karyawan. Harap hubungi Admin.');
        }

        return Inertia::render('Admin/Pengajuan/Create', [
            'masterAkun' => $masterAkun,
            'masterProgram' => $masterProgram,
            'masterPajak' => $masterPajak,
            'karyawan' => $karyawan,
            'masterVendor' => $masterVendor, // <-- BARU
        ]);
    }

    /**
     * Menyimpan pengajuan baru ke database.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input (PERBAIKAN)
        $validator = $request->validate([
            'id_pengaju' => 'required|integer|exists:tbl_karyawan,id',
            'id_departemen' => 'required|integer|exists:tbl_departemen,id',
            'judul_pengajuan' => 'required|string|max:255',
            'tgl_pengajuan' => 'required|date',
            'tipe_pengajuan' => 'required|in:Langsung,UangMuka',
            'metode_pembayaran' => 'required|in:Transfer,Cash',
            'id_vendor_penerima' => 'nullable|required_if:metode_pembayaran,Transfer|required_if:tipe_pengajuan,Langsung|exists:tbl_vendor,id',
            'attachment' => 'required_if:tipe_pengajuan,Langsung|file|mimes:pdf,jpg,png,jpeg|max:2048', 
            'catatan_header' => 'nullable|string',
            'total_nominal_diajukan' => 'required|numeric|min:1',
            
            'items' => 'required_if:tipe_pengajuan,Langsung|array',
            'items.*.deskripsi_item' => 'required_if:tipe_pengajuan,Langsung|string|max:255',
            'items.*.nominal_item' => 'required_if:tipe_pengajuan,Langsung|numeric|min:1',
            'items.*.id_program' => 'required_if:tipe_pengajuan,Langsung|integer|exists:tbl_program_kerja,id',
            'items.*.id_akun' => 'required_if:tipe_pengajuan,Langsung|integer|exists:tbl_akun_gl,id',
            'items.*.id_pajak' => 'nullable|integer|exists:tbl_pajak,id',
        ]);

        $tanggal = Carbon::parse($validator['tgl_pengajuan']);
        $warnings = [];

        try {
            DB::transaction(function () use ($validator, $tanggal, &$warnings, $request) { 
                
                // --- PENYIMPANAN FILE ---
                $path = null;
                if ($request->hasFile('attachment')) {
                    $path = $request->file('attachment')->store('attachments', 'public');
                }
                // --- AKHIR PENYIMPANAN FILE ---

                // 3. Buat Pengajuan Header
                $pengajuanHeader = PengajuanHeader::create([
                    'nomor_pengajuan' => 'PJ-' . time(),
                    'judul_pengajuan' => $validator['judul_pengajuan'],
                    'id_pengaju' => $validator['id_pengaju'],
                    'id_departemen' => $validator['id_departemen'],
                    'tgl_pengajuan' => $tanggal,
                    'tipe_pengajuan' => $validator['tipe_pengajuan'],
                    'total_nominal_diajukan' => $validator['total_nominal_diajukan'],
                    'status_global' => 'Pending Approval',
                    'catatan_header' => $validator['catatan_header'],
                    'attachment_path' => $path, 
                    'metode_pembayaran' => $validator['metode_pembayaran'],
                    'id_vendor_penerima' => $validator['id_vendor_penerima'] ?? null,
                ]);

                // 4. Proses Tipe Pengajuan 'Langsung'
                if ($validator['tipe_pengajuan'] == 'Langsung') {
                    
                    foreach ($validator['items'] as $index => $item) {
                        
                        $budgetCheck = $this->budgetCheckService->check(
                            $validator['id_departemen'],
                            $item['id_akun'],
                            $item['id_program'],
                            $item['nominal_item'],
                            $tanggal
                        );

                        if (!$budgetCheck['success']) {
                            throw new \Exception("Gagal Budget Check (Item: {$item['deskripsi_item']}): " . $budgetCheck['message']);
                        }
                        if ($budgetCheck['warning']) {
                            $warnings[] = $item['deskripsi_item'] . ": " . $budgetCheck['warning'];
                        }

                        // 7. Simpan Detail Pengajuan
                        $pengajuanHeader->detail()->create([
                            'deskripsi_item' => $item['deskripsi_item'],
                            'nominal_item' => $item['nominal_item'],
                            'id_program' => $item['id_program'],
                            'id_akun' => $item['id_akun'],
                            'id_pajak' => $item['id_pajak'] ?? null,
                        ]);

                        // 8. Ikat Anggaran (Commit Budget)
                        $this->budgetCheckService->commitBudget(
                            $budgetCheck['budget_id'],
                            $item['nominal_item']
                        );
                    }
                } 
                // 9. Proses Tipe Pengajuan 'Uang Muka'
                else if ($validator['tipe_pengajuan'] == 'UangMuka') {
                    // (Logika UangMuka tidak berubah)
                    $akunDefaultUangMuka = AkunGl::firstOrCreate(['kode_akun' => '1-5000-01'], ['nama_akun' => 'Uang Muka Karyawan', 'tipe_akun' => 'Aset'])->id;
                    $programDefaultUangMuka = ProgramKerja::firstOrCreate(['nama_program' => 'OPERASIONAL UMUM'])->id; 

                    $budgetCheck = $this->budgetCheckService->check(
                        $validator['id_departemen'],
                        $akunDefaultUangMuka,
                        $programDefaultUangMuka,
                        $validator['total_nominal_diajukan'],
                        $tanggal
                    );

                    if (!$budgetCheck['success']) {
                        throw new \Exception("Gagal Budget Check (Uang Muka): " . $budgetCheck['message']);
                    }
                    if ($budgetCheck['warning']) {
                        $warnings[] = $budgetCheck['warning'];
                    }

                    $this->budgetCheckService->commitBudget(
                        $budgetCheck['budget_id'],
                        $validator['total_nominal_diajukan']
                    );
                }

            }); // 10. Transaksi Selesai (Commit)

        } catch (\Exception $e) {
            Log::error('Gagal menyimpan pengajuan: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menyimpan pengajuan: ' . $e->getMessage())
                ->withInput();
        }

        // 12. Sukses
        return redirect()->route('admin.pengajuan.index')
            ->with('success', 'Pengajuan berhasil dibuat.' . (count($warnings) > 0 ? ' (Dengan Peringatan: ' . implode(', ', $warnings) . ')' : ''));
    }

    /**
     * Menampilkan detail spesifik dari satu pengajuan.
     */
    public function show(PengajuanHeader $pengajuan)
    {
        $pengajuan->load([
            'pengaju:id,nama_lengkap', 
            'departemen:id,nama_departemen',
            'vendorPenerima:id,nama_vendor,nama_bank,nomor_rekening,atas_nama_rekening', // <-- BARU
            'detail.akunGl:id,kode_akun,nama_akun', 
            'detail.programKerja:id,nama_program',
            'detail.pajak:id,kode_pajak',
            'logPersetujuan.approver:id,nama_lengkap,jabatan' 
        ]);

        return Inertia::render('Admin/Pengajuan/Show', [
            'pengajuan' => $pengajuan
        ]);
    }

    /**
     * Menampilkan form untuk mengedit pengajuan.
     */
    public function edit(PengajuanHeader $pengajuan)
    {
        if ($pengajuan->status_global !== 'Pending Approval') {
             return redirect()->route('admin.pengajuan.show', $pengajuan->id)
                ->with('error', 'Pengajuan ini tidak dapat diedit karena sudah diproses.');
        }
        
        $pengajuan->load('detail');

        $masterAkun = AkunGl::whereIn('tipe_akun', ['Biaya', 'Biaya Modal'])
                            ->orderBy('kode_akun')->get(['id', 'nama_akun', 'kode_akun']);
        $masterProgram = ProgramKerja::orderBy('nama_program')->get(['id', 'nama_program']);
        $masterPajak = Pajak::orderBy('kode_pajak')->get(['id', 'kode_pajak']);
        $masterVendor = Vendor::orderBy('nama_vendor')->get(['id', 'nama_vendor']); // <-- BARU
        
        $karyawan = Auth::user()->karyawan()->with('departemen')->first();

        return Inertia::render('Admin/Pengajuan/Edit', [
            'pengajuan' => $pengajuan, 
            'masterAkun' => $masterAkun,
            'masterProgram' => $masterProgram,
            'masterPajak' => $masterPajak,
            'karyawan' => $karyawan,
            'masterVendor' => $masterVendor, // <-- BARU
        ]);
    }

    /**
     * Memperbarui pengajuan yang ada di database.
     */
    public function update(Request $request, PengajuanHeader $pengajuan)
    {
        if ($pengajuan->status_global !== 'Pending Approval') {
             return redirect()->route('admin.pengajuan.show', $pengajuan->id)
                ->with('error', 'Pengajuan ini tidak dapat diedit karena sudah diproses.');
        }

        // 1. Validasi Input (PERBAIKAN)
        $validator = $request->validate([
            'id_pengaju' => 'required|integer|exists:tbl_karyawan,id',
            'id_departemen' => 'required|integer|exists:tbl_departemen,id',
            'judul_pengajuan' => 'required|string|max:255',
            'tgl_pengajuan' => 'required|date',
            'tipe_pengajuan' => 'required|in:Langsung,UangMuka',
            'metode_pembayaran' => 'required|in:Transfer,Cash',
            'id_vendor_penerima' => 'nullable|required_if:metode_pembayaran,Transfer|required_if:tipe_pengajuan,Langsung|exists:tbl_vendor,id',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048', // Nullable saat update
            'catatan_header' => 'nullable|string',
            'total_nominal_diajukan' => 'required|numeric|min:1',
            
            'items' => 'required_if:tipe_pengajuan,Langsung|array',
            'items.*.id' => 'nullable|integer', 
            'items.*.deskripsi_item' => 'required_if:tipe_pengajuan,Langsung|string|max:255',
            'items.*.nominal_item' => 'required_if:tipe_pengajuan,Langsung|numeric|min:1',
            'items.*.id_program' => 'required_if:tipe_pengajuan,Langsung|integer|exists:tbl_program_kerja,id',
            'items.*.id_akun' => 'required_if:tipe_pengajuan,Langsung|integer|exists:tbl_akun_gl,id',
            'items.*.id_pajak' => 'nullable|integer|exists:tbl_pajak,id',
        ]);

        $tanggal = Carbon::parse($validator['tgl_pengajuan']);
        $warnings = [];

        try {
            DB::transaction(function () use ($validator, $tanggal, $pengajuan, &$warnings, $request) {
                
                // 2a. Kembalikan (Un-commit) semua anggaran lama
                $pengajuanLama = $pengajuan->load('detail');
                if ($pengajuanLama->tipe_pengajuan == 'Langsung') {
                    foreach ($pengajuanLama->detail as $oldItem) {
                        $budget = BudgetMaster::where('tahun', $pengajuanLama->tgl_pengajuan->year)
                                    ->where('id_departemen', $pengajuanLama->id_departemen)
                                    ->where('id_akun', $oldItem->id_akun)
                                    ->where('id_program', $oldItem->id_program)
                                    ->first();
                        if ($budget) {
                            $budget->decrement('anggaran_terikat_ytd', $oldItem->nominal_item);
                        }
                    }
                }
                // TODO: Tambah logika un-commit untuk UangMuka
                
                // 2b. Hapus detail lama
                $pengajuan->detail()->delete();

                // --- PENYIMPANAN FILE UPDATE ---
                $path = $pengajuan->attachment_path; // Path lama
                if ($request->hasFile('attachment')) {
                    // Hapus file lama jika ada
                    if ($path) {
                        Storage::disk('public')->delete($path);
                    }
                    // Simpan file baru
                    $path = $request->file('attachment')->store('attachments', 'public');
                }
                // --- AKHIR PENYIMPANAN FILE ---

                // 3. Update Header
                $pengajuan->update([
                    'judul_pengajuan' => $validator['judul_pengajuan'],
                    'tgl_pengajuan' => $tanggal,
                    'tipe_pengajuan' => $validator['tipe_pengajuan'],
                    'total_nominal_diajukan' => $validator['total_nominal_diajukan'],
                    'catatan_header' => $validator['catatan_header'],
                    'attachment_path' => $path,
                    'metode_pembayaran' => $validator['metode_pembayaran'],
                    'id_vendor_penerima' => $validator['id_vendor_penerima'] ?? null,
                ]);

                // 4. Proses Tipe Pengajuan 'Langsung' (Sama seperti store)
                if ($validator['tipe_pengajuan'] == 'Langsung') {
                    foreach ($validator['items'] as $item) {
                        
                        $budgetCheck = $this->budgetCheckService->check(
                            $validator['id_departemen'],
                            $item['id_akun'],
                            $item['id_program'],
                            $item['nominal_item'],
                            $tanggal
                        );

                        if (!$budgetCheck['success']) {
                            throw new \Exception("Gagal Budget Check (Item: {$item['deskripsi_item']}): " . $budgetCheck['message']);
                        }
                        if ($budgetCheck['warning']) {
                            $warnings[] = $item['deskripsi_item'] . ": " . $budgetCheck['warning'];
                        }

                        // Buat detail baru
                        $pengajuan->detail()->create([
                            'deskripsi_item' => $item['deskripsi_item'],
                            'nominal_item' => $item['nominal_item'],
                            'id_program' => $item['id_program'],
                            'id_akun' => $item['id_akun'],
                            'id_pajak' => $item['id_pajak'] ?? null,
                        ]);

                        // Ikat anggaran baru
                        $this->budgetCheckService->commitBudget(
                            $budgetCheck['budget_id'],
                            $item['nominal_item']
                        );
                    }
                } 
                // 5. TODO: Proses Tipe Pengajuan 'Uang Muka' (Update)
                // ... 

            }); // 6. Transaksi Selesai (Commit)

        } catch (\Exception $e) {
            Log::error('Gagal memperbarui pengajuan: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal memperbarui pengajuan: ' . $e->getMessage())
                ->withInput();
        }

        // 7. Sukses
        return redirect()->route('admin.pengajuan.show', $pengajuan->id) 
            ->with('success', 'Pengajuan berhasil diperbarui.' . (count($warnings) > 0 ? ' (Dengan Peringatan: ' . implode(', ', $warnings) . ')' : ''));
    }

    /**
     * Menghapus pengajuan dari database.
     */
    public function destroy(PengajuanHeader $pengajuan)
    {
        // TODO: Tambahkan Policy & Logika Un-commit Budget
        // ...
        // $pengajuan->delete();
        // return redirect()->route('admin.pengajuan.index')->with('success', 'Pengajuan dihapus.');
    }
}