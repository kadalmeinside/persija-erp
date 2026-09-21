<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosAnggaran;
use App\Models\ProgramKerja;
use App\Models\AkunGl;
use App\Models\Departemen;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class PosAnggaranController extends Controller
{
    public function index(Request $request)
    {
        $showArsip = $request->boolean('show_arsip', false);

        $query = PosAnggaran::with(['programKerja.departemen', 'akunGl', 'delegasi'])
            ->when(!$showArsip, fn($q) => $q->where('is_active', true));

        if ($request->id_program) {
            $query->where('id_program_kerja', $request->id_program);
        }

        if ($request->search) {
            $query->whereHas('akunGl', function ($q) use ($request) {
                $q->where('nama_akun', 'like', '%' . $request->search . '%')
                  ->orWhere('kode_akun', 'like', '%' . $request->search . '%');
            });
        }

        $posAnggarans = $query->paginate(10)->withQueryString();

        $programs     = ProgramKerja::with('departemen')->orderBy('nama_program')->get();
        $akuns        = AkunGl::whereIn('tipe_akun', ['Biaya', 'Biaya Modal', 'Aset', 'Pendapatan'])
                            ->where('is_active', true)
                            ->orderBy('kode_akun')->get();
        $departemens  = Departemen::orderBy('nama_departemen')->get();

        return Inertia::render('Admin/PosAnggaran/Index', [
            'posAnggarans' => $posAnggarans,
            'programs'     => $programs,
            'akuns'        => $akuns,
            'departemens'  => $departemens,
            'filters'      => $request->only(['search', 'id_program', 'show_arsip']),
            'showArsip'    => $showArsip,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_program_kerja'    => 'required|exists:tbl_program_kerja,id',
            'id_akun_gl'          => 'required|exists:tbl_akun_gl,id',
            'delegasi_departemen' => 'array',
            'delegasi_departemen.*' => 'exists:tbl_departemen,id',
        ]);

        // Cek apakah kombinasi ini sudah ada (termasuk yang diarsipkan)
        $existing = PosAnggaran::withTrashed()
            ->where('id_program_kerja', $request->id_program_kerja)
            ->where('id_akun_gl', $request->id_akun_gl)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                // Restore jika pernah dihapus
                $existing->restore();
                $existing->update(['is_active' => true, 'catatan_arsip' => null]);
                $pos = $existing;
            } elseif (!$existing->is_active) {
                // Aktifkan kembali jika diarsipkan
                $existing->update(['is_active' => true, 'catatan_arsip' => null]);
                $pos = $existing;
            } else {
                throw ValidationException::withMessages(['id_akun_gl' => 'Akun ini sudah terdaftar di program kerja tersebut.']);
            }
        } else {
            $pos = PosAnggaran::create([
                'id_program_kerja' => $request->id_program_kerja,
                'id_akun_gl'       => $request->id_akun_gl,
                'is_active'        => true,
            ]);
        }

        if ($request->has('delegasi_departemen')) {
            $pos->delegasi()->sync($request->delegasi_departemen);
        }

        return redirect()->back()->with('success', 'Pos Anggaran berhasil ditambahkan.');
    }

    public function update(Request $request, PosAnggaran $posAnggaran)
    {
        $request->validate([
            'delegasi_departemen'   => 'array',
            'delegasi_departemen.*' => 'exists:tbl_departemen,id',
        ]);

        if ($request->has('delegasi_departemen')) {
            $posAnggaran->delegasi()->sync($request->delegasi_departemen);
        }

        return redirect()->back()->with('success', 'Delegasi Pos Anggaran berhasil diperbarui.');
    }

    /**
     * Arsipkan pos anggaran (soft-archive):
     * - is_active = false → tidak muncul di dropdown transaksi baru
     * - Data historis (BudgetMaster, transaksi) tetap utuh
     */
    public function archive(Request $request, PosAnggaran $posAnggaran)
    {
        $request->validate([
            'catatan_arsip' => 'nullable|string|max:255',
        ]);

        $posAnggaran->update([
            'is_active'     => false,
            'catatan_arsip' => $request->catatan_arsip ?? 'Diarsipkan — tidak digunakan di tahun anggaran berikutnya.',
        ]);

        return redirect()->back()->with('success', "Pos Anggaran '{$posAnggaran->akunGl?->nama_akun}' berhasil diarsipkan.");
    }

    /**
     * Aktifkan kembali pos anggaran yang diarsipkan.
     */
    public function restore(PosAnggaran $posAnggaran)
    {
        $posAnggaran->update([
            'is_active'     => true,
            'catatan_arsip' => null,
        ]);

        return redirect()->back()->with('success', "Pos Anggaran '{$posAnggaran->akunGl?->nama_akun}' berhasil diaktifkan kembali.");
    }

    /**
     * Hard delete — hanya jika tidak ada BudgetMaster yang mereferensikannya.
     */
    public function destroy(PosAnggaran $posAnggaran)
    {
        $used = \App\Models\BudgetMaster::where('id_pos_anggaran', $posAnggaran->id)->exists();

        if ($used) {
            return redirect()->back()->with(
                'error',
                'Pos Anggaran ini tidak dapat dihapus permanen karena sudah memiliki data anggaran. Gunakan "Arsipkan" sebagai gantinya.'
            );
        }

        $posAnggaran->delete(); // soft delete
        return redirect()->back()->with('success', 'Pos Anggaran berhasil dihapus.');
    }
}
