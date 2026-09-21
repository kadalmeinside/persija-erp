<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pelanggan::query();

        if ($request->search) {
            $query->where('nama_pelanggan', 'like', '%' . $request->search . '%')
                  ->orWhere('kode_pelanggan', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        $customers = $query->orderBy('nama_pelanggan')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Revenue/Customer/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'email' => 'nullable|email|max:100',
            'telepon' => 'nullable|string|max:50',
            'alamat' => 'nullable|string',
            'npwp' => 'nullable|string|max:50',
        ]);

        Pelanggan::create([
            'kode_pelanggan' => DocumentNumberService::customer(),
            'nama_pelanggan' => $request->nama_pelanggan,
            'email' => $request->email,
            'telepon' => $request->telepon,
            'alamat' => $request->alamat,
            'npwp' => $request->npwp,
            'is_active' => true
        ]);

        return redirect()->back()->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pelanggan $customer)
    {
        $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'email' => 'nullable|email|max:100',
            'telepon' => 'nullable|string|max:50',
            'alamat' => 'nullable|string',
            'npwp' => 'nullable|string|max:50',
            'is_active' => 'boolean'
        ]);

        $customer->update($request->only(['nama_pelanggan', 'email', 'telepon', 'alamat', 'npwp', 'is_active']));

        return redirect()->back()->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pelanggan $customer)
    {
        // Guard: cek apakah pelanggan memiliki invoice
        if ($customer->invoices()->exists()) {
            return redirect()->back()->with('error', 'Pelanggan tidak dapat dihapus karena sudah memiliki invoice yang tercatat.');
        }

        $customer->delete();
        return redirect()->back()->with('success', 'Pelanggan berhasil dihapus.');
    }
}
