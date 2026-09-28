<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AbsensiService;

class AbsensiController extends Controller
{
    /**
     * Get today's attendance status.
     */
    public function today(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        $absensi = \App\Models\Absensi::where('id_karyawan', $karyawan->id)
            ->where('tanggal', date('Y-m-d'))
            ->first();

        return response()->json([
            'data' => [
                'clock_in' => $absensi ? $absensi->waktu_masuk : null,
                'clock_out' => $absensi ? $absensi->waktu_keluar : null,
                'status' => $absensi ? $absensi->status_kehadiran : 'Belum Absen'
            ]
        ], 200);
    }

    /**
     * Handle Clock In from Mobile App.
     */
    public function clockIn(Request $request, AbsensiService $service)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'nullable|image|max:2048', // Max 2MB
            'is_dinas_luar' => 'nullable|boolean',
            'catatan' => 'nullable|string'
        ]);

        $karyawan = $request->user()->karyawan;
        $isDinasLuar = filter_var($request->input('is_dinas_luar', false), FILTER_VALIDATE_BOOLEAN);

        $result = $service->processClockIn(
            $karyawan,
            $request->latitude,
            $request->longitude,
            $request->file('photo'),
            $isDinasLuar,
            $request->catatan
        );

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => [
                'id' => $result['data']->id,
                'clock_in' => $result['data']->waktu_masuk
            ]
        ], 201);
    }

    /**
     * Handle Clock Out from Mobile App.
     */
    public function clockOut(Request $request, AbsensiService $service)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'nullable|image|max:2048',
        ]);

        $karyawan = $request->user()->karyawan;

        $result = $service->processClockOut(
            $karyawan,
            $request->latitude,
            $request->longitude,
            $request->file('photo')
        );

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => [
                'id' => $result['data']->id,
                'clock_out' => $result['data']->waktu_keluar
            ]
        ], 200);
    }

    /**
     * Get attendance history for the user.
     */
    public function history(Request $request)
    {
        $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|digits:4',
        ]);

        $karyawan = $request->user()->karyawan;
        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));

        $history = \App\Models\Absensi::where('id_karyawan', $karyawan->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->orderBy('tanggal', 'desc')
            ->paginate(10);

        return \App\Http\Resources\AbsensiResource::collection($history);
    }
}
