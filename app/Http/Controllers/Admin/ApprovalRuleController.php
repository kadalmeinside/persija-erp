<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRule;
use App\Models\Departemen;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $departemenId = $request->input('id_departemen');
        $tipe = $request->input('tipe'); // Filter by Tipe
        
        // Get all departments for filter
        $departemens = Departemen::orderBy('nama_departemen')->get();

        // Get rules if department is selected
        $rules = [];
        if ($departemenId) {
            $query = ApprovalRule::with(['karyawanApprover', 'departemen'])
                        ->where('id_departemen', $departemenId);
            
            if ($tipe) {
                $query->where('tipe', $tipe);
            }

            $rules = $query->orderBy('tipe')
                        ->orderBy('level_order', 'asc')
                        ->get();
        }

        // Get all employees for selection
        $karyawans = Karyawan::orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'jabatan']);

        return Inertia::render('Admin/ApprovalRules/Index', [
            'departemens' => $departemens,
            'karyawans' => $karyawans,
            'rules' => $rules,
            'filters' => $request->only(['id_departemen', 'tipe'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_departemen'        => 'required|exists:tbl_departemen,id',
            'id_karyawan_approver' => 'required|exists:tbl_karyawan,id',
            'level_order'          => 'required|integer|min:1',
            'label_aksi'           => 'required|string|max:50',
            'tipe'                 => 'required|in:Pengajuan,Cuti,Pinjaman,Invoice,PettyCash',
            'min_amount'           => 'nullable|numeric|min:0',
        ]);

        // Check for duplicate level in same department AND same tipe
        $exists = ApprovalRule::where('id_departemen', $request->id_departemen)
                    ->where('tipe', $request->tipe)
                    ->where('level_order', $request->level_order)
                    ->exists();

        if ($exists) {
            return redirect()->back()->with('error', "Level {$request->level_order} untuk tipe {$request->tipe} sudah ada.");
        }

        ApprovalRule::create($request->only([
            'id_departemen',
            'id_karyawan_approver',
            'level_order',
            'label_aksi',
            'tipe',
            'min_amount',
        ]));

        return redirect()->back()->with('success', 'Rule approval berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'id_karyawan_approver' => 'required|exists:tbl_karyawan,id',
            'level_order'          => 'required|integer|min:1',
            'label_aksi'           => 'required|string|max:50',
            'tipe'                 => 'required|in:Pengajuan,Cuti,Pinjaman,Invoice,PettyCash',
            'min_amount'           => 'nullable|numeric|min:0',
        ]);

        $rule = ApprovalRule::findOrFail($id);

        // Check for duplicate level if level or tipe is changed
        if ($rule->level_order != $request->level_order || $rule->tipe != $request->tipe) {
            $exists = ApprovalRule::where('id_departemen', $rule->id_departemen)
                        ->where('tipe', $request->tipe)
                        ->where('level_order', $request->level_order)
                        ->where('id', '!=', $id)
                        ->exists();

            if ($exists) {
                return redirect()->back()->with('error', "Level {$request->level_order} untuk tipe {$request->tipe} sudah ada.");
            }
        }

        $rule->update([
            'id_karyawan_approver' => $request->id_karyawan_approver,
            'level_order'          => $request->level_order,
            'label_aksi'           => $request->label_aksi,
            'tipe'                 => $request->tipe,
            'min_amount'           => $request->min_amount,
        ]);

        return redirect()->back()->with('success', 'Rule approval berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $rule = ApprovalRule::findOrFail($id);
        $rule->delete();

        return redirect()->back()->with('success', 'Rule approval berhasil dihapus.');
    }
}
