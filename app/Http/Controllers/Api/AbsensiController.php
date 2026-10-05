<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AbsensiService;
use App\Models\AttendanceChallenge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
     * Issue a short-lived, single-use challenge for a mobile attendance action.
     */
    public function challenge(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:clock-in,clock-out',
            'security_metadata' => 'nullable|array',
            'security_metadata.platform' => 'nullable|string|in:android,ios,other',
            'security_metadata.app_version' => 'nullable|string|max:30',
            'security_metadata.build_number' => 'nullable|string|max:30',
            'security_metadata.model' => 'nullable|string|max:100',
            'security_metadata.manufacturer' => 'nullable|string|max:100',
            'security_metadata.os_version' => 'nullable|string|max:50',
            'security_metadata.is_physical_device' => 'nullable|boolean',
            'security_metadata.is_rooted_or_jailbroken' => 'nullable|boolean',
            'security_metadata.is_developer_mode_enabled' => 'nullable|boolean',
            'security_metadata.is_usb_debugging_enabled' => 'nullable|boolean',
            'security_metadata.is_mock_location' => 'nullable|boolean',
            'security_metadata.attestation_provider' => 'nullable|string|max:80',
        ]);

        $user = $request->user();
        if (!$user->karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        $nonce = bin2hex(random_bytes(32));
        $challenge = AttendanceChallenge::create([
            'user_id' => $user->id,
            'action' => $validated['action'],
            'request_id' => (string) Str::uuid(),
            'nonce_hash' => hash('sha256', $nonce),
            'security_metadata' => $validated['security_metadata'] ?? null,
            'expires_at' => now()->addMinutes(5),
        ]);

        return response()->json([
            'data' => [
                'action' => $challenge->action,
                'request_id' => $challenge->request_id,
                'nonce' => $nonce,
                'expires_at' => $challenge->expires_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Handle Clock In from Mobile App.
     */
    public function clockIn(Request $request, AbsensiService $service)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'required|image|max:2048', // Max 2MB
            'request_id' => 'required|uuid',
            'nonce' => 'required|string|size:64',
            'is_dinas_luar' => 'nullable|boolean',
            'catatan' => 'nullable|string'
        ]);

        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }
        $isDinasLuar = filter_var($request->input('is_dinas_luar', false), FILTER_VALIDATE_BOOLEAN);

        $challenge = $this->consumeChallenge($request, 'clock-in');
        if ($challenge instanceof \Illuminate\Http\JsonResponse) {
            return $challenge;
        }

        $result = $service->processClockIn($karyawan, $request->latitude, $request->longitude, $request->file('photo'), $isDinasLuar, $request->catatan);

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
            'photo' => 'required|image|max:2048',
            'request_id' => 'required|uuid',
            'nonce' => 'required|string|size:64',
        ]);

        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        $challenge = $this->consumeChallenge($request, 'clock-out');
        if ($challenge instanceof \Illuminate\Http\JsonResponse) {
            return $challenge;
        }

        $result = $service->processClockOut($karyawan, $request->latitude, $request->longitude, $request->file('photo'));

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

    private function consumeChallenge(Request $request, string $action): ?\Illuminate\Http\JsonResponse
    {
        $challenge = DB::transaction(function () use ($request, $action) {
            $challenge = AttendanceChallenge::where('user_id', $request->user()->id)
                ->where('action', $action)
                ->where('request_id', $request->input('request_id'))
                ->lockForUpdate()
                ->first();

            if (!$challenge ||
                $challenge->consumed_at ||
                $challenge->expires_at->isPast() ||
                !hash_equals($challenge->nonce_hash, hash('sha256', $request->input('nonce')))) {
                return null;
            }

            // Reserve the challenge before processing the upload to prevent concurrent replay.
            $challenge->forceFill(['consumed_at' => now()])->save();
            return $challenge;
        });

        if (!$challenge) {
            return response()->json([
                'message' => 'Challenge absensi tidak valid atau sudah kedaluwarsa. Silakan mulai ulang proses absensi.',
            ], 422);
        }

        return null;
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
        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

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
