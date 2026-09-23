<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LokasiKantor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LokasiKantorController extends Controller
{
    public function index()
    {
        $lokasiKantors = LokasiKantor::orderBy('nama_kantor', 'asc')->get();
        return Inertia::render('Admin/LokasiKantor/Index', [
            'lokasiKantors' => $lokasiKantors
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kantor' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_meter' => 'required|integer|min:1',
            'is_active' => 'boolean'
        ]);

        LokasiKantor::create($request->all());

        return redirect()->back()->with('success', 'Cabang baru berhasil ditambahkan.');
    }

    public function update(Request $request, LokasiKantor $lokasiKantor)
    {
        $request->validate([
            'nama_kantor' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_meter' => 'required|integer|min:1',
            'is_active' => 'boolean'
        ]);

        $lokasiKantor->update($request->all());

        return redirect()->back()->with('success', 'Data cabang berhasil diperbarui.');
    }

    public function destroy(LokasiKantor $lokasiKantor)
    {
        // Prevent deleting if it's currently used by employees?
        // Let's just allow soft delete as per model if it uses SoftDeletes.
        $lokasiKantor->delete();

        return redirect()->back()->with('success', 'Cabang berhasil dihapus.');
    }
}
