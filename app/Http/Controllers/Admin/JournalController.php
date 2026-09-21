<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JurnalHeader;
use App\Models\JurnalDetail;
use App\Models\AkunGl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class JournalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = JurnalHeader::with(['pembuat', 'detail'])->orderBy('tgl_jurnal', 'desc');

        if ($request->search) {
            $query->where('nomor_jurnal', 'like', '%' . $request->search . '%')
                  ->orWhere('deskripsi_jurnal', 'like', '%' . $request->search . '%');
        }

        $journals = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Accounting/Journal/Index', [
            'journals' => $journals,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accounts = AkunGl::orderBy('kode_akun')->get(['id', 'kode_akun', 'nama_akun']);
        return Inertia::render('Admin/Accounting/Journal/Create', [
            'accounts' => $accounts
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tgl_jurnal' => 'required|date',
            'deskripsi_jurnal' => 'required|string|max:255',
            'details' => 'required|array|min:2',
            'details.*.id_akun' => 'required|exists:tbl_akun_gl,id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.kredit' => 'required|numeric|min:0',
        ]);

        // Validate Balance
        $totalDebit = collect($request->details)->sum('debit');
        $totalCredit = collect($request->details)->sum('kredit');

        if (abs($totalDebit - $totalCredit) > 0.01) { // Tolerance for float
            return back()->withErrors(['details' => 'Jurnal tidak seimbang (Unbalanced). Total Debit: ' . $totalDebit . ', Total Kredit: ' . $totalCredit]);
        }

        try {
            DB::transaction(function () use ($request) {
                $header = JurnalHeader::create([
                    'nomor_jurnal' => 'JR-' . date('Ymd') . '-' . rand(1000, 9999), // Simple numbering for now
                    'tgl_jurnal' => $request->tgl_jurnal,
                    'deskripsi_jurnal' => $request->deskripsi_jurnal,
                    'tipe_transaksi' => 'Manual',
                    'status' => 'Posted',
                    'created_by' => Auth::id(),
                    'sumber_modul' => 'GL'
                ]);

                foreach ($request->details as $detail) {
                    if ($detail['debit'] > 0 || $detail['kredit'] > 0) {
                        JurnalDetail::create([
                            'id_jurnal' => $header->id,
                            'id_akun' => $detail['id_akun'],
                            'debit' => $detail['debit'],
                            'kredit' => $detail['kredit'],
                            'keterangan_baris' => $detail['keterangan_baris'] ?? null
                        ]);
                    }
                }
            });

            return redirect()->route('admin.journals.index')->with('success', 'Jurnal berhasil diposting.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan jurnal: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $journal = JurnalHeader::with(['detail.akun', 'pembuat'])->findOrFail($id);
        return Inertia::render('Admin/Accounting/Journal/Show', [
            'journal' => $journal
        ]);
    }
}
