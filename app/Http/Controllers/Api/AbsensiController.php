<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
    public function clockIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'nullable|image|max:2048', // Max 2MB
            'is_dinas_luar' => 'nullable|boolean',
            'catatan' => 'nullable|string'
        ]);

        $karyawan = $request->user()->karyawan;
        $today = date('Y-m-d');

        $exists = \App\Models\Absensi::where('id_karyawan', $karyawan->id)
            ->where('tanggal', $today)
            ->first();

        if ($exists && $exists->waktu_masuk) {
            return response()->json(['message' => 'Anda sudah melakukan absen masuk hari ini.'], 422);
        }

        $fotoPath = null;
        if ($request->hasFile('photo')) {
            $fotoPath = $request->file('photo')->store('absensi/' . date('Y/m'), 'public');
        }

        $absensi = \App\Models\Absensi::updateOrCreate(
            ['id_karyawan' => $karyawan->id, 'tanggal' => $today],
            [
                'waktu_masuk' => date('H:i:s'),
                'lat_masuk' => $request->latitude,
                'lng_masuk' => $request->longitude,
                'foto_masuk' => $fotoPath,
                'status_kehadiran' => 'Hadir',
                'is_dinas_luar' => $request->is_dinas_luar ?? false,
                'catatan' => $request->catatan,
                'id_lokasi_kantor' => $karyawan->id_lokasi_kantor,
            ]
        );

        return response()->json([
            'message' => 'Berhasil absen masuk.',
            'data' => [
                'id' => $absensi->id,
                'clock_in' => $absensi->waktu_masuk
            ]
        ], 201);
    }

    /**
     * Handle Clock Out from Mobile App.
     */
    public function clockOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'nullable|image|max:2048',
        ]);

        $karyawan = $request->user()->karyawan;
        $today = date('Y-m-d');

        $absensi = \App\Models\Absensi::where('id_karyawan', $karyawan->id)
            ->where('tanggal', $today)
            ->first();

        if (!$absensi || !$absensi->waktu_masuk) {
            return response()->json(['message' => 'Anda belum absen masuk hari ini.'], 422);
        }

        if ($absensi->waktu_keluar) {
            return response()->json(['message' => 'Anda sudah melakukan absen pulang hari ini.'], 422);
        }

        $fotoPath = null;
        if ($request->hasFile('photo')) {
            $fotoPath = $request->file('photo')->store('absensi/' . date('Y/m'), 'public');
        }

        $absensi->update([
            'waktu_keluar' => date('H:i:s'),
            'lat_keluar' => $request->latitude,
            'lng_keluar' => $request->longitude,
            'foto_keluar' => $fotoPath
        ]);

        return response()->json([
            'message' => 'Berhasil absen pulang.',
            'data' => [
                'id' => $absensi->id,
                'clock_out' => $absensi->waktu_keluar
            ]
        ], 200);
    }

    /**
     * Get attendance history for the user.
     */
    public function history(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));

        $history = \App\Models\Absensi::where('id_karyawan', $karyawan->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json([
            'data' => $history->map(function ($item) {
                return [
                    'id' => $item->id,
                    'date' => $item->tanggal,
                    'clock_in' => $item->waktu_masuk,
                    'clock_out' => $item->waktu_keluar,
                    'status' => $item->status_kehadiran
                ];
            })
        ], 200);
    }
}
