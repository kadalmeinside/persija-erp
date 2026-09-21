<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class HariLiburController extends Controller
{
    public function index(Request $request)
    {
        $query = HariLibur::query();

        if ($request->has('search')) {
            $query->where('keterangan', 'like', '%' . $request->search . '%');
        }

        $holidays = $query->orderBy('tanggal', 'desc')->paginate(10);

        return Inertia::render('Admin/HariLibur/Index', [
            'holidays' => $holidays,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date|unique:tbl_hari_libur,tanggal',
            'keterangan' => 'required|string|max:255',
        ]);

        HariLibur::create($request->only(['tanggal', 'keterangan']));

        return redirect()->back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function update(Request $request, HariLibur $hariLibur)
    {
        $request->validate([
            'tanggal' => 'required|date|unique:tbl_hari_libur,tanggal,' . $hariLibur->id,
            'keterangan' => 'required|string|max:255',
        ]);

        $hariLibur->update($request->only(['tanggal', 'keterangan']));

        return redirect()->back()->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(HariLibur $hariLibur)
    {
        $hariLibur->delete();
        return redirect()->back()->with('success', 'Hari libur berhasil dihapus.');
    }

    public function fetch(Request $request)
    {
        // Fetch from api-harilibur.vercel.app (Free & Open Source)
        // Endpoint: https://api-harilibur.vercel.app/api?year={year}
        
        $year = $request->input('year', date('Y'));
        
        try {
            $response = Http::get("https://api-harilibur.vercel.app/api?year={$year}");
            
            if ($response->successful()) {
                $data = $response->json();
                $count = 0;

                foreach ($data as $holiday) {
                    if ($holiday['is_national_holiday']) {
                        $date = $holiday['holiday_date']; // Format: YYYY-MM-DD
                        $desc = $holiday['holiday_name'];

                        // Create or Update
                        $exists = HariLibur::where('tanggal', $date)->exists();
                        if (!$exists) {
                            HariLibur::create([
                                'tanggal' => $date,
                                'keterangan' => $desc
                            ]);
                            $count++;
                        }
                    }
                }

                return redirect()->back()->with('success', "Berhasil menyinkronkan $count hari libur baru untuk tahun $year.");
            } else {
                return redirect()->back()->with('error', 'Gagal mengambil data dari API.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage());
        }
    }
}
