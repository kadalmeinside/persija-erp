<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LokasiKantor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AbsensiSettingController extends Controller
{
    /**
     * Display the attendance settings page.
     */
    public function index()
    {
        // Get the first location or create a dummy one if empty so form doesn't crash
        $lokasiKantor = LokasiKantor::first();

        if (!$lokasiKantor) {
            $lokasiKantor = [
                'id' => null,
                'nama_kantor' => '',
                'latitude' => '',
                'longitude' => '',
                'radius_meter' => 50,
                'is_active' => true,
            ];
        }

        return Inertia::render('Admin/Absensi/Settings', [
            'lokasiKantor' => $lokasiKantor
        ]);
    }

    /**
     * Update or create the attendance settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'nama_kantor' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_meter' => 'required|integer|min:1',
            'is_active' => 'required|boolean',
        ]);

        $lokasiKantor = LokasiKantor::first();

        if ($lokasiKantor) {
            $lokasiKantor->update($request->only([
                'nama_kantor',
                'latitude',
                'longitude',
                'radius_meter',
                'is_active'
            ]));
        } else {
            LokasiKantor::create($request->only([
                'nama_kantor',
                'latitude',
                'longitude',
                'radius_meter',
                'is_active'
            ]));
        }

        return redirect()->back()->with('success', 'Pengaturan lokasi absensi berhasil diperbarui.');
    }
}
