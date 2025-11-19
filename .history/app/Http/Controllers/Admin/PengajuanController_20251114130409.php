<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// Models
use App\Models\PengajuanHeader;
use App\Models\PengajuanDetail;
use App\Models\AkunGl;
use App\Models\ProgramKerja;
use App\Models\Pajak;
use App\Models\BudgetMaster;
use App\Models\Karyawan;

// Service
use App\Services\BudgetCheckService;

class PengajuanController extends Controller
{
    // Injeksi Service Class
    protected $budgetCheckService;

    public function __construct(BudgetCheckService $budgetCheckService)
    {
        $this->budgetCheckService = $budgetCheckService;
    }

    /**
     * Menampilkan form untuk membuat pengajuan baru.
     * Ini adalah halaman Vue Anda.
     */
    public function create()
    {
        // Mengambil data Karyawan yang sedang login
        $karyawan = Karyawan::where('user_id', Auth::id())->first();

        if (!$karyawan) {
            // Handle jika user tidak terhubung ke data karyawan
            abort(403, 'User Anda tidak terhubung ke data Karyawan.');
        }

        // Kirim data master ke frontend Vue sebagai props
        return Inertia::render('Admin/Pengajuan/Create', [
            'masterAkun' => AkunGl::whereIn('tipe_akun', ['Biaya', 'Biaya Modal'])->get(['id', 'kode_akun', 'nama_akun']),
            'masterProgram' => ProgramKerja::all(['id', 'nama_program']),
            'masterPajak' => Pajak::all(['id', 'kode_pajak', 'persentase']),
            'karyawanLogin' => [
                'id' => $karyawan->id,
                'nama' => $karyawan->nama_lengkap,
                'id_departemen' => $karyawan->id_departemen,
                'nama_departemen' => $karyawan->departemen->nama_departemen ?? 'N/A',
            ]
        ]);
    }

    /**
     * Menyimpan pengajuan baru (dari form Vue).
     */
    public function store(Request $request)
    {
        // 1. Validasi data header
        $validatedHeader = $request->validate([
            'id_pengaju' => 'required|integer|exists:tbl_karyawan,id',
            'id_departemen' => 'required|integer|exists:tbl_departemen,id',
            'tgl_pengajuan' => 'required|date',
            'tipe_pengajuan' => 'required|in:Langsung,UangMuka',
            'catatan_header' => 'nullable|string',
            'total_nominal_diajukan' => 'required|numeric|min:1',
            'items' => 'required|array|min:1',
            'items.*.deskripsi_item' => 'required|string|max:255',
            'items.*.nominal_item' => 'required|numeric|min:1',
            'items.*.id_program' => 'required|integer|exists:tbl_program_kerja,id',
            'items.*.id_akun' => 'required|integer|exists:tbl_akun_gl,id',
            'items.*.id_pajak' => 'nullable|integer|exists:tbl_pajak,id',
        ]);

        $tanggalPengajuan = Carbon::parse($validatedHeader['tgl_pengajuan']);
        $warnings = [];
        $budgetUpdates = []; // Untuk mengikat anggaran

        // 2. Loop dan Pengecekan Anggaran (Budget Check)
        if ($validatedHeader['tipe_pengajuan'] == 'Langsung') 
        {
            foreach ($validatedHeader['items'] as $item) {
                $checkResult = $this->budgetCheckService->check(
                    $validatedHeader['id_departemen'],
                    $item['id_akun'],
                    $item['id_program'],
                    $item['nominal_item'],
                    $tanggalPengajuan
                );

                // Jika Hard Check Gagal, batalkan semua
                if (!$checkResult['success']) {
                    return redirect()->back()->withErrors([
                        'budget' => "Error pada item '{$item['deskripsi_item']}': " . $checkResult['message']
                    ]);
                }

                // Kumpulkan peringatan (Soft Check)
                if ($checkResult['warning']) {
                    $warnings[] = "Item '{$item['deskripsi_item']}': " . $checkResult['warning'];
                }
                
                // Kumpulkan data untuk update 'anggaran_terikat_ytd'
                $budgetId = $checkResult['budget_id'];
                if (!isset($budgetUpdates[$budgetId])) {
                    $budgetUpdates[$budgetId] = 0;
                }
                $budgetUpdates[$budgetId] += $item['nominal_item'];
            }
        }
        // TODO: Tambahkan logika budget check untuk 'UangMuka' (mungkin perlu akun default)

        // 3. Simpan ke Database (jika semua budget check lolos)
        try {
            DB::transaction(function () use ($validatedHeader, $budgetUpdates) {
                
                // a. Simpan Header
                $header = PengajuanHeader::create([
                    'nomor_pengajuan' => 'PR-' . time(), // TODO: Buat nomorator otomatis
                    'id_pengaju' => $validatedHeader['id_pengaju'],
                    'id_departemen' => $validatedHeader['id_departemen'],
                    'tgl_pengajuan' => $validatedHeader['tgl_pengajuan'],
                    'tipe_pengajuan' => $validatedHeader['tipe_pengajuan'],
                    'total_nominal_diajukan' => $validatedHeader['total_nominal_diajukan'],
                    'catatan_header' => $validatedHeader['catatan_header'],
                    'status_global' => 'Pending Approval', // Status awal
                ]);

                // b. Simpan Detail
                if ($validatedHeader['tipe_pengajuan'] == 'Langsung') {
                    foreach ($validatedHeader['items'] as $item) {
                        $header->detail()->create([
                            'deskripsi_item' => $item['deskripsi_item'],
                            'nominal_item' => $item['nominal_item'],
                            'id_program' => $item['id_program'],
                            'id_akun' => $item['id_akun'],
                            'id_pajak' => $item['id_pajak'] ?? null,
                            // 'id_vendor' => $item['id_vendor'] ?? null, // Tambahkan jika ada
                        ]);
                    }
                }

                // c. Ikat Anggaran (Update anggaran_terikat_ytd)
                foreach ($budgetUpdates as $budgetId => $nominal) {
                    BudgetMaster::where('id', $budgetId)->increment('anggaran_terikat_ytd', $nominal);
                }

                // d. Buat Log Persetujuan Awal (Opsional)
                // ...
            });

        } catch (\Exception $e) {
            Log::error("Gagal menyimpan pengajuan: " . $e->getMessage());
            return redirect()->back()->withErrors([
                'database' => "Gagal menyimpan ke database: " . $e->getMessage()
            ]);
        }
        
        // 4. Kembalikan ke halaman index dengan pesan sukses (dan peringatan)
        return redirect()->route('admin.dashboard') // Ganti ke route index pengajuan nanti
            ->with('success', 'Pengajuan berhasil dibuat.')
            ->with('warnings', $warnings);
    }

    // TODO: Tambahkan method index(), show(), approve(), reject(), pay()
}