<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\TaxType;
use App\Models\AkunGl;
use App\Models\ProgramKerja;
use Inertia\Inertia;

class TaxTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxType::with(['akunGl', 'programKerja'])->orderBy('kode_pajak', 'asc');

        if ($request->search) {
            $query->where('nama_pajak', 'like', '%' . $request->search . '%')
                  ->orWhere('kode_pajak', 'like', '%' . $request->search . '%');
        }

        $taxTypes = $query->paginate(10)->withQueryString();
        $glAccounts = AkunGl::select('id', 'kode_akun', 'nama_akun')->get();
        $programs = ProgramKerja::select('id', 'nama_program')->get();

        return Inertia::render('Admin/Finance/Tax/Index', [
            'taxTypes' => $taxTypes,
            'glAccounts' => $glAccounts,
            'programs' => $programs,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_pajak' => 'required|string|max:20|unique:tbl_tax_types,kode_pajak',
            'nama_pajak' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0',
            'tipe' => 'required|in:PPN,PPh',
            'id_akun_gl' => 'required|exists:tbl_akun_gl,id',
            'id_program' => 'required|exists:tbl_program_kerja,id',
            'is_active' => 'boolean'
        ]);

        TaxType::create($request->only(['kode_pajak', 'nama_pajak', 'rate', 'tipe', 'id_akun_gl', 'id_program', 'is_active']));

        return redirect()->back()->with('success', 'Tipe pajak berhasil dibuat.');
    }

    public function update(Request $request, TaxType $taxType)
    {
        $request->validate([
            'kode_pajak' => 'required|string|max:20|unique:tbl_tax_types,kode_pajak,' . $taxType->id,
            'nama_pajak' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0',
            'tipe' => 'required|in:PPN,PPh',
            'id_akun_gl' => 'required|exists:tbl_akun_gl,id',
            'id_program' => 'required|exists:tbl_program_kerja,id',
            'is_active' => 'boolean'
        ]);

        $taxType->update($request->only(['kode_pajak', 'nama_pajak', 'rate', 'tipe', 'id_akun_gl', 'id_program', 'is_active']));

        return redirect()->back()->with('success', 'Tipe pajak berhasil diperbarui.');
    }

    public function destroy(TaxType $taxType)
    {
        // Check usage before delete (Optional, but good practice)
        // For now, allow delete
        $taxType->delete();

        return redirect()->back()->with('success', 'Tipe pajak berhasil dihapus.');
    }
}
