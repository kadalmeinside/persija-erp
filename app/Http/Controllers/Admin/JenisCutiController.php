<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisCuti;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JenisCutiController extends Controller
{
    public function index()
    {
        $jenisCuti = JenisCuti::all();
        return Inertia::render('Admin/Master/JenisCuti/Index', [
            'jenisCuti' => $jenisCuti
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_cuti' => 'required|string|max:255',
            'kuota_default' => 'required|integer|min:0',
            'bisa_mundur' => 'boolean',
            'khusus_perempuan' => 'boolean',
            'is_unlimited' => 'boolean',
            'wajib_lampiran' => 'boolean',
        ]);

        JenisCuti::create($data);

        return redirect()->back()->with('success', 'Jenis Cuti berhasil ditambahkan.');
    }

    public function update(Request $request, JenisCuti $jenisCuti)
    {
        $data = $request->validate([
            'nama_cuti' => 'required|string|max:255',
            'kuota_default' => 'required|integer|min:0',
            'bisa_mundur' => 'boolean',
            'khusus_perempuan' => 'boolean',
            'is_unlimited' => 'boolean',
            'wajib_lampiran' => 'boolean',
        ]);

        $jenisCuti->update($data);

        return redirect()->back()->with('success', 'Jenis Cuti berhasil diperbarui.');
    }

    public function destroy(JenisCuti $jenisCuti)
    {
        $jenisCuti->delete();
        return redirect()->back()->with('success', 'Jenis Cuti berhasil dihapus.');
    }
}
