<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::query();

        if ($request->search) {
            $query->where('nama_vendor', 'like', '%' . $request->search . '%')
                  ->orWhere('kode_vendor', 'like', '%' . $request->search . '%');
        }

        $vendors = $query->orderBy('nama_vendor')->paginate(10);

        return Inertia::render('Admin/Vendor/Index', [
            'vendors' => $vendors,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_vendor' => 'required|string|max:255',
            'telepon_vendor' => 'nullable|string|max:20',
            'kategori_vendor' => 'required|string|in:Umum,Material,Jasa',
            'alamat_vendor' => 'nullable|string',
        ]);

        Vendor::create([
            'kode_vendor' => DocumentNumberService::vendor(),
            'nama_vendor' => $request->nama_vendor,
            'telepon_vendor' => $request->telepon_vendor,
            'kategori_vendor' => $request->kategori_vendor,
            'alamat_vendor' => $request->alamat_vendor,
            'is_active' => true
        ]);

        return redirect()->back()->with('success', 'Vendor berhasil ditambahkan.');
    }

    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'nama_vendor' => 'required|string|max:255',
            'telepon_vendor' => 'nullable|string|max:20',
            'kategori_vendor' => 'required|string|in:Umum,Material,Jasa',
            'alamat_vendor' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $vendor->update($request->only(['nama_vendor', 'telepon_vendor', 'kategori_vendor', 'alamat_vendor', 'is_active']));

        return redirect()->back()->with('success', 'Vendor berhasil diperbarui.');
    }

    public function destroy(Vendor $vendor)
    {
        try {
            $vendor->delete();
            return redirect()->back()->with('success', 'Vendor berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus vendor. Mungkin sedang digunakan.');
        }
    }
}
