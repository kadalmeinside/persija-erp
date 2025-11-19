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
        // 1. Validasi Input
        $validator = $request->validate([
            'id_karyawan' => 'required|integer|exists:tbl_karyawan,id',
            'id_departemen' => 'required|integer|exists:tbl_departemen,id',
            'tgl_pengajuan' => 'required|date',
            'tipe_pengajuan' => 'required|in:Langsung,UangMuka',
            'catatan_header' => 'nullable|string',
            'total_nominal_diajukan' => 'required|numeric|min:1',
            
            // Validasi 'items' HANYA jika tipe_pengajuan = 'Langsung'
            'items' => 'required_if:tipe_pengajuan,Langsung|array',
            'items.*.deskripsi_item' => 'required_if:tipe_pengajuan,Langsung|string|max:255',
            'items.*.nominal_item' => 'required_if:tipe_pengajuan,Langsung|numeric|min:1',
            'items.*.id_program' => 'required_if:tipe_pengajuan,Langsung|integer|exists:tbl_program_kerja,id',
            'items.*.id_akun' => 'required_if:tipe_pengajuan,Langsung|integer|exists:tbl_akun_gl,id',
            'items.*.id_pajak' => 'nullable|integer|exists:tbl_pajak,id',
        ]);

        $tanggal = Carbon::parse($validator['tgl_pengajuan']);
        $warnings = [];

        // 2. Mulai Transaksi Database
        try {
            DB::transaction(function () use ($validator, $tanggal, &$warnings) {
                
                // 3. Buat Pengajuan Header
                $pengajuanHeader = PengajuanHeader::create([
                    'nomor_pengajuan' => 'PJ-' . time(), // TODO: Buat nomor pengajuan yang proper
                    'id_pengaju' => $validator['id_karyawan'],
                    'id_departemen' => $validator['id_departemen'],
                    'tgl_pengajuan' => $tanggal,
                    'tipe_pengajuan' => $validator['tipe_pengajuan'],
                    'total_nominal_diajukan' => $validator['total_nominal_diajukan'],
                    'status_global' => 'Pending Approval',
                    'catatan_header' => $validator['catatan_header'],
                ]);

                // 4. Proses Tipe Pengajuan 'Langsung' (dengan rincian item)
                if ($validator['tipe_pengajuan'] == 'Langsung') {
                    foreach ($validator['items'] as $item) {
                        
                        // 5. Panggil BudgetCheckService untuk setiap item
                        $budgetCheck = $this->budgetCheckService->check(
                            $validator['id_departemen'],
                            $item['id_akun'],
                            $item['id_program'],
                            $item['nominal_item'],
                            $tanggal
                        );

                        // 6. Jika Gagal, batalkan semua (Rollback)
                        if (!$budgetCheck['success']) {
                            // Melempar exception akan otomatis me-rollback transaksi
                            throw new \Exception("Gagal Budget Check: " . $budgetCheck['message']);
                        }

                        // Kumpulkan peringatan (jika ada)
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
                            // 'id_vendor' => $item['id_vendor'] ?? null, // Tambahkan jika ada
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
                    // TODO: Tentukan akun & program default untuk Uang Muka
                    // Untuk saat ini, kita akan 'hardcode' (ini harus diperbaiki di pengaturan)
                    
                    $akunDefaultUangMuka = 1; // GANTI DENGAN ID AKUN UANG MUKA
                    $programDefaultUangMuka = 1; // GANTI DENGAN ID PROGRAM DEFAULT

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

                    // Ikat Anggaran (Commit Budget)
                    $this->budgetCheckService->commitBudget(
                        $budgetCheck['budget_id'],
                        $validator['total_nominal_diajukan']
                    );
                }

            }); // 10. Transaksi Selesai (Commit)

        } catch (\Exception $e) {
            // 11. Jika terjadi error (seperti budget tidak cukup), tangkap di sini
            Log::error('Gagal menyimpan pengajuan: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menyimpan pengajuan: ' . $e->getMessage())
                ->withInput();
        }

        // 12. Sukses
        return redirect()->route('admin.pengajuan.index')
            ->with('success', 'Pengajuan berhasil dibuat.' . (count($warnings) > 0 ? ' (Dengan Peringatan: ' . implode(', ', $warnings) . ')' : ''));
    }

    // TODO: Tambahkan method index(), show(), approve(), reject(), pay()
}