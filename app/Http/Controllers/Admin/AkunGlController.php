<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunGl;
use App\Models\JurnalDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AkunGlController extends Controller
{
    public function index(Request $request)
    {
        $showArsip = $request->boolean('show_arsip', false);

        $query = AkunGl::query()
            ->when(!$showArsip, fn($q) => $q->where('is_active', true));

        if ($request->search) {
            $query->where(fn($q) =>
                $q->where('nama_akun', 'like', '%' . $request->search . '%')
                  ->orWhere('kode_akun', 'like', '%' . $request->search . '%')
            );
        }

        if ($request->tipe) {
            $query->where('tipe_akun', $request->tipe);
        }

        $akunGls = $query->orderBy('kode_akun')->paginate(15)->withQueryString();

        return Inertia::render('Admin/AkunGl/Index', [
            'akunGls'   => $akunGls,
            'filters'   => $request->only(['search', 'tipe', 'show_arsip']),
            'showArsip' => $showArsip,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_akun' => 'required|string|unique:tbl_akun_gl,kode_akun|max:20',
            'nama_akun' => 'required|string|max:100',
            'tipe_akun' => 'required|in:Aset,Kewajiban,Ekuitas,Pendapatan,Biaya,Biaya Modal',
        ]);

        AkunGl::create([
            'kode_akun' => $request->kode_akun,
            'nama_akun' => $request->nama_akun,
            'tipe_akun' => $request->tipe_akun,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Akun GL berhasil ditambahkan.');
    }

    public function update(Request $request, AkunGl $akunGl)
    {
        $request->validate([
            'kode_akun' => 'required|string|max:20|unique:tbl_akun_gl,kode_akun,' . $akunGl->id,
            'nama_akun' => 'required|string|max:100',
            'tipe_akun' => 'required|in:Aset,Kewajiban,Ekuitas,Pendapatan,Biaya,Biaya Modal',
        ]);

        $akunGl->update($request->only(['kode_akun', 'nama_akun', 'tipe_akun']));

        return redirect()->back()->with('success', 'Akun GL berhasil diperbarui.');
    }

    /**
     * Nonaktifkan akun GL (soft-archive).
     * Akun lama tidak dihapus — semua jurnal historis tetap valid.
     * Akun yang dinonaktifkan tidak akan muncul di dropdown transaksi baru.
     */
    public function archive(AkunGl $akunGl)
    {
        // Cek apakah akun punya transaksi — jika ya, tetap izinkan arsip (hanya nonaktifkan)
        $akunGl->update(['is_active' => false]);

        return redirect()->back()->with('success', "Akun GL '{$akunGl->kode_akun} - {$akunGl->nama_akun}' berhasil dinonaktifkan.");
    }

    /**
     * Aktifkan kembali akun GL yang dinonaktifkan.
     */
    public function restore(AkunGl $akunGl)
    {
        $akunGl->update(['is_active' => true]);

        return redirect()->back()->with('success', "Akun GL '{$akunGl->kode_akun} - {$akunGl->nama_akun}' berhasil diaktifkan kembali.");
    }

    /**
     * Hapus permanen — hanya jika akun belum pernah digunakan dalam jurnal.
     */
    public function destroy(AkunGl $akunGl)
    {
        $digunakan = JurnalDetail::where('id_akun', $akunGl->id)->exists();

        if ($digunakan) {
            return redirect()->back()->with(
                'error',
                "Akun '{$akunGl->kode_akun}' tidak dapat dihapus karena sudah tercatat dalam jurnal. Gunakan 'Nonaktifkan' sebagai gantinya."
            );
        }

        try {
            $akunGl->delete(); // soft delete
            return redirect()->back()->with('success', 'Akun GL berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus. Akun ini masih direferensikan.');
        }
    }
}
