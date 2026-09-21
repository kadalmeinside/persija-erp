<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Aset;
use App\Models\Penyusutan;
use App\Models\AkunGl;
use App\Services\GLService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Aset::query();

        if ($request->search) {
            $query->where('nama_aset', 'like', '%' . $request->search . '%')
                  ->orWhere('kode_aset', 'like', '%' . $request->search . '%');
        }

        $assets = $query->orderBy('tgl_perolehan', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Asset/Index', [
            'assets' => $assets,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accounts = AkunGl::orderBy('kode_akun')->get();
        
        return Inertia::render('Admin/Asset/Create', [
            'accounts' => $accounts
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_aset' => 'required|string|max:255',
            'kategori' => 'required|string',
            'tgl_perolehan' => 'required|date',
            'harga_perolehan' => 'required|numeric|min:0',
            'nilai_sisa' => 'required|numeric|min:0',
            'umur_manfaat_bulan' => 'required|integer|min:1',
            'metode_penyusutan' => 'required|string',
            'id_akun_aset' => 'required|exists:tbl_akun_gl,id',
            'id_akun_akumulasi_penyusutan' => 'required|exists:tbl_akun_gl,id',
            'id_akun_beban_penyusutan' => 'required|exists:tbl_akun_gl,id',
        ]);

        Aset::create([
            'kode_aset' => 'AST-' . date('Ym') . '-' . rand(100, 999),
            'nama_aset' => $request->nama_aset,
            'kategori' => $request->kategori,
            'tgl_perolehan' => $request->tgl_perolehan,
            'harga_perolehan' => $request->harga_perolehan,
            'nilai_sisa' => $request->nilai_sisa,
            'umur_manfaat_bulan' => $request->umur_manfaat_bulan,
            'metode_penyusutan' => $request->metode_penyusutan,
            'id_akun_aset' => $request->id_akun_aset,
            'id_akun_akumulasi_penyusutan' => $request->id_akun_akumulasi_penyusutan,
            'id_akun_beban_penyusutan' => $request->id_akun_beban_penyusutan,
            'status' => 'Active',
            'deskripsi' => $request->deskripsi
        ]);

        return redirect()->route('admin.assets.index')->with('success', 'Aset berhasil didaftarkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Aset $asset)
    {
        $asset->load(['akunAset', 'akunAkumulasi', 'akunBeban', 'penyusutan']);
        
        // Calculate current Book Value
        $totalDepreciation = $asset->penyusutan->sum('nilai_penyusutan');
        $currentBookValue = $asset->harga_perolehan - $totalDepreciation;

        return Inertia::render('Admin/Asset/Show', [
            'asset' => $asset,
            'currentBookValue' => $currentBookValue
        ]);
    }

    /**
     * Run Depreciation for a specific month.
     */
    public function runDepreciation(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m' // e.g., 2024-11
        ]);

        $date = Carbon::createFromFormat('Y-m', $request->month)->endOfMonth();
        
        try {
            DB::transaction(function () use ($date) {
                // 1. Get Active Assets
                $assets = Aset::where('status', 'Active')->get();
                $count = 0;

                foreach ($assets as $asset) {
                    // Check if already depreciated for this month
                    $exists = Penyusutan::where('id_aset', $asset->id)
                        ->where('tgl_penyusutan', $date->format('Y-m-d'))
                        ->exists();
                    
                    if ($exists) continue;

                    // Calculate Depreciation (Straight Line)
                    // (Cost - Salvage) / Useful Life
                    // But we need to check if fully depreciated
                    
                    $totalDepreciated = Penyusutan::where('id_aset', $asset->id)->sum('nilai_penyusutan');
                    $bookValue = $asset->harga_perolehan - $totalDepreciated;
                    
                    if ($bookValue <= $asset->nilai_sisa) {
                        $asset->update(['status' => 'Fully Depreciated']);
                        continue;
                    }

                    $monthlyDepreciation = ($asset->harga_perolehan - $asset->nilai_sisa) / $asset->umur_manfaat_bulan;
                    
                    // Adjust if remaining book value is less than calculated monthly
                    if ($bookValue - $monthlyDepreciation < $asset->nilai_sisa) {
                        $monthlyDepreciation = $bookValue - $asset->nilai_sisa;
                    }

                    if ($monthlyDepreciation <= 0) continue;

                    // Create Depreciation Record
                    $penyusutan = Penyusutan::create([
                        'id_aset' => $asset->id,
                        'tgl_penyusutan' => $date->format('Y-m-d'),
                        'nilai_buku_awal' => $bookValue,
                        'nilai_penyusutan' => $monthlyDepreciation,
                        'nilai_buku_akhir' => $bookValue - $monthlyDepreciation,
                        'is_posted' => true,
                        'jurnal_ref' => 'DEP-' . $date->format('Ym')
                    ]);

                    // Auto-Post to GL
                    // Debit: Depreciation Expense
                    // Credit: Accumulated Depreciation
                    
                    $glDetails = [
                        [
                            'id_akun' => $asset->id_akun_beban_penyusutan,
                            'debit' => $monthlyDepreciation,
                            'kredit' => 0,
                            'keterangan' => 'Penyusutan ' . $asset->nama_aset . ' ' . $date->format('M Y')
                        ],
                        [
                            'id_akun' => $asset->id_akun_akumulasi_penyusutan,
                            'debit' => 0,
                            'kredit' => $monthlyDepreciation,
                            'keterangan' => 'Akumulasi Penyusutan ' . $asset->nama_aset
                        ]
                    ];

                    GLService::createJournal(
                        $date->format('Y-m-d'),
                        'Penyusutan Aset ' . $asset->kode_aset,
                        $glDetails,
                        'FA', // Fixed Assets
                        $asset->id,
                        'Depreciation'
                    );

                    $count++;
                }
            });

            return back()->with('success', 'Proses penyusutan selesai.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses penyusutan: ' . $e->getMessage());
        }
    }
}
