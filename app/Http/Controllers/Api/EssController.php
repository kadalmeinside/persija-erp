<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\DinasLuar;
use App\Models\KoreksiAbsensi;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EssController extends Controller
{
    /**
     * ─── 1. KOREKSI ABSENSI ────────────────────────────────────────────────
     */

    public function getAttendanceCorrections(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 404);
        }

        $corrections = KoreksiAbsensi::where('id_karyawan', $karyawan->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $corrections->items(),
            'meta' => [
                'current_page' => $corrections->currentPage(),
                'last_page' => $corrections->lastPage(),
                'total' => $corrections->total(),
            ],
        ]);
    }

    public function submitAttendanceCorrection(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'waktu_masuk_baru' => 'nullable|date_format:H:i',
            'waktu_keluar_baru' => 'nullable|date_format:H:i',
            'alasan' => 'required|string|max:500',
            'id_absensi' => 'nullable|integer',
        ]);

        $correction = KoreksiAbsensi::create([
            'id_karyawan' => $karyawan->id,
            'id_absensi' => $validated['id_absensi'] ?? null,
            'tanggal' => $validated['tanggal'],
            'waktu_masuk_baru' => $validated['waktu_masuk_baru'] ?? null,
            'waktu_keluar_baru' => $validated['waktu_keluar_baru'] ?? null,
            'alasan' => $validated['alasan'],
            'status' => 'Pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan koreksi absensi berhasil dikirim.',
            'data' => $correction,
        ], 201);
    }

    /**
     * ─── 2. DINAS LUAR ─────────────────────────────────────────────────────
     */

    public function getBusinessTrips(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 404);
        }

        $trips = DinasLuar::where('id_karyawan', $karyawan->id)
            ->latest('tgl_mulai')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $trips->items(),
            'meta' => [
                'current_page' => $trips->currentPage(),
                'last_page' => $trips->lastPage(),
                'total' => $trips->total(),
            ],
        ]);
    }

    public function submitBusinessTrip(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'nama_kegiatan' => 'required|string|max:200',
            'lokasi_tujuan' => 'required|string|max:200',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $trip = DinasLuar::create([
            'id_karyawan' => $karyawan->id,
            'nomor_surat_tugas' => 'ST-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
            'nama_kegiatan' => $validated['nama_kegiatan'],
            'lokasi_tujuan' => $validated['lokasi_tujuan'],
            'tgl_mulai' => $validated['tgl_mulai'],
            'tgl_selesai' => $validated['tgl_selesai'],
            'keterangan' => $validated['keterangan'] ?? null,
            'status' => 'Pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan dinas luar berhasil didaftarkan.',
            'data' => $trip,
        ], 201);
    }

    /**
     * ─── 3. PAYSLIP READ-ONLY ──────────────────────────────────────────────
     */

    public function getPayslips(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 404);
        }

        $details = PayrollDetail::where('id_karyawan', $karyawan->id)
            ->whereHas('payroll', fn ($q) => $q->whereIn('status', ['Approved', 'Paid']))
            ->with('payroll')
            ->latest('id')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'periode' => $d->payroll?->bulan_periode ?? '-',
                'tgl_payroll' => is_string($d->payroll?->tgl_payroll)
                    ? $d->payroll->tgl_payroll
                    : ($d->payroll?->tgl_payroll?->format('Y-m-d') ?? '-'),
                'status' => $d->payroll?->status instanceof \BackedEnum
                    ? $d->payroll->status->value
                    : (string) ($d->payroll?->status ?? 'Draft'),
                'gaji_pokok' => (float) $d->gaji_pokok,
                'total_tunjangan' => (float) $d->total_tunjangan,
                'total_potongan' => (float) $d->total_potongan,
                'gaji_bersih' => (float) $d->gaji_bersih,
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $details,
        ]);
    }

    public function getPayslipDetail(Request $request, $id)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 404);
        }

        $detail = PayrollDetail::where('id', $id)
            ->where('id_karyawan', $karyawan->id)
            ->with(['payroll', 'karyawan.departemen'])
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $detail->id,
                'periode' => $detail->payroll?->bulan_periode ?? '-',
                'tgl_payroll' => is_string($detail->payroll?->tgl_payroll)
                    ? $detail->payroll->tgl_payroll
                    : ($detail->payroll?->tgl_payroll?->format('Y-m-d') ?? '-'),
                'status' => $detail->payroll?->status instanceof \BackedEnum
                    ? $detail->payroll->status->value
                    : (string) ($detail->payroll?->status ?? 'Draft'),
                'karyawan' => [
                    'nama' => $detail->karyawan?->nama_lengkap ?? '-',
                    'nip' => $detail->karyawan?->nomor_induk_karyawan ?? '-',
                    'jabatan' => $detail->karyawan?->jabatan ?? '-',
                    'departemen' => $detail->karyawan?->departemen?->nama_departemen ?? '-',
                ],
                'ringkasan' => [
                    'gaji_pokok' => (float) $detail->gaji_pokok,
                    'total_tunjangan' => (float) $detail->total_tunjangan,
                    'total_potongan' => (float) $detail->total_potongan,
                    'gaji_bersih' => (float) $detail->gaji_bersih,
                ],
                'rincian_komponen' => is_array($detail->rincian_komponen)
                    ? $detail->rincian_komponen
                    : json_decode($detail->rincian_komponen, true) ?? [],
            ],
        ]);
    }

    /**
     * ─── 4. REIMBURSEMENT ──────────────────────────────────────────────────
     */

    public function getReimbursements(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 404);
        }

        $claims = Reimbursement::where('id_karyawan', $karyawan->id)
            ->latest('tgl_transaksi')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $claims->items(),
            'meta' => [
                'current_page' => $claims->currentPage(),
                'last_page' => $claims->lastPage(),
                'total' => $claims->total(),
            ],
        ]);
    }

    public function submitReimbursement(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'judul_klaim' => 'required|string|max:200',
            'kategori' => 'required|string|in:Transport,Konsumsi,Medis,Operasional,Lainnya',
            'tgl_transaksi' => 'required|date',
            'total_biaya' => 'required|numeric|min:1',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $claim = Reimbursement::create([
            'id_karyawan' => $karyawan->id,
            'nomor_reimbursement' => 'RMB-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
            'judul_klaim' => $validated['judul_klaim'],
            'kategori' => $validated['kategori'],
            'tgl_transaksi' => $validated['tgl_transaksi'],
            'total_biaya' => $validated['total_biaya'],
            'keterangan' => $validated['keterangan'] ?? null,
            'status' => 'Pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan reimbursement berhasil didaftarkan.',
            'data' => $claim,
        ], 201);
    }
}
