<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class DepartemenController extends Controller
{
    public function index(Request $request)
    {
        $query = Departemen::query();

        if ($request->search) {
            $query->where('nama_departemen', 'like', '%' . $request->search . '%');
        }

        $departemens = $query->orderBy('nama_departemen')->paginate(10);

        $expenseAccounts = \App\Models\AkunGl::where('tipe_akun', 'Expense')
            ->orWhere('tipe_akun', 'Other Expense')
            ->orderBy('kode_akun')
            ->get(['id', 'kode_akun', 'nama_akun']);

        $karyawans = \App\Models\Karyawan::where('status_karyawan', '!=', 'Resign')
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'jabatan']);

        return Inertia::render('Admin/Departemen/Index', [
            'departemens' => $departemens->through(function ($dept) {
                return [
                    'id' => $dept->id,
                    'nama_departemen' => $dept->nama_departemen,
                    'id_akun_beban_gaji' => $dept->id_akun_beban_gaji,
                    'id_karyawan_kepala' => $dept->id_karyawan_kepala,
                    'kepala' => $dept->kepala
                ];
            }),
            'expenseAccounts' => $expenseAccounts,
            'karyawans' => $karyawans,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_departemen' => 'required|string|max:255|unique:tbl_departemen,nama_departemen',
            'id_akun_beban_gaji' => 'nullable|exists:tbl_akun_gl,id',
            'id_karyawan_kepala' => 'nullable|exists:tbl_karyawan,id'
        ]);

        Departemen::create($request->only('nama_departemen', 'id_akun_beban_gaji', 'id_karyawan_kepala'));

        return redirect()->back()->with('success', 'Departemen berhasil dibuat.');
    }

    public function update(Request $request, Departemen $departemen)
    {
        $request->validate([
            'nama_departemen' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tbl_departemen', 'nama_departemen')->ignore($departemen->id),
            ],
            'id_akun_beban_gaji' => 'nullable|exists:tbl_akun_gl,id',
            'id_karyawan_kepala' => 'nullable|exists:tbl_karyawan,id'
        ]);

        $departemen->update($request->only('nama_departemen', 'id_akun_beban_gaji', 'id_karyawan_kepala'));

        return redirect()->back()->with('success', 'Departemen berhasil diperbarui.');
    }

    public function destroy(Departemen $departemen)
    {
        // Check for dependencies (Programs, Users, etc.)
        // For now, we'll let the database constraints handle it or add explicit checks later
        try {
            $departemen->delete();
            return redirect()->back()->with('success', 'Departemen berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus departemen yang sedang digunakan.');
        }
    }
}
