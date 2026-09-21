<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramKerja;
use App\Models\Departemen;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramKerjaController extends Controller
{
    public function index(Request $request)
    {
        $query = ProgramKerja::with('departemen');

        if ($request->search) {
            $query->where('nama_program', 'like', '%' . $request->search . '%');
        }

        if ($request->id_departemen) {
            $query->where('id_departemen', $request->id_departemen);
        }

        $programs = $query->orderBy('id_departemen')->orderBy('nama_program')->paginate(10);
        $departemens = Departemen::orderBy('nama_departemen')->get();

        return Inertia::render('Admin/ProgramKerja/Index', [
            'programs' => $programs,
            'departemens' => $departemens,
            'filters' => $request->only(['search', 'id_departemen'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_program' => 'required|string|max:255',
            'id_departemen' => 'required|exists:tbl_departemen,id'
        ]);

        ProgramKerja::create($request->only(['nama_program', 'id_departemen']));

        return redirect()->back()->with('success', 'Program Kerja berhasil dibuat.');
    }

    public function update(Request $request, ProgramKerja $programKerja)
    {
        $request->validate([
            'nama_program' => 'required|string|max:255',
            'id_departemen' => 'required|exists:tbl_departemen,id'
        ]);

        $programKerja->update($request->only(['nama_program', 'id_departemen']));

        return redirect()->back()->with('success', 'Program Kerja berhasil diperbarui.');
    }

    public function destroy(ProgramKerja $programKerja)
    {
        if ($programKerja->budgetMaster()->exists()) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus program yang sudah memiliki anggaran.');
        }

        $programKerja->delete();

        return redirect()->back()->with('success', 'Program Kerja berhasil dihapus.');
    }
}
