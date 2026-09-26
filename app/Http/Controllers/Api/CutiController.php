<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SaldoCuti;
use App\Models\PengajuanCuti;
use Illuminate\Support\Carbon;

class CutiController extends Controller
{
    /**
     * Get available leave types (Jenis Cuti) for dropdown.
     */
    public function jenis()
    {
        $jenis = \App\Models\JenisCuti::all();
        
        return response()->json([
            'data' => $jenis->map(function ($j) {
                return [
                    'id' => $j->id,
                    'nama_cuti' => $j->nama_cuti,
                    'kuota_default' => $j->kuota_default,
                    'wajib_lampiran' => (bool) $j->wajib_lampiran,
                ];
            })
        ], 200);
    }

    /**
     * Get leave balances for the authenticated user.
     */
    public function balances(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        $balances = SaldoCuti::with('jenisCuti')
            ->where('id_karyawan', $karyawan->id)
            ->where('tahun_periode', date('Y'))
            ->get();

        return response()->json([
            'data' => $balances->map(function ($saldo) {
                return [
                    'id' => $saldo->id,
                    'jenis_cuti_id' => $saldo->id_jenis_cuti,
                    'jenis_cuti' => $saldo->jenisCuti?->nama_cuti,
                    'saldo_awal' => $saldo->saldo_awal,
                    'saldo_terpakai' => $saldo->saldo_terpakai,
                    'saldo_akhir' => $saldo->saldo_akhir,
                ];
            })
        ], 200);
    }

    /**
     * Get user's leave requests history.
     */
    public function requests(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        $requests = PengajuanCuti::with('jenisCuti')
            ->where('id_karyawan', $karyawan->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $requests->map(function ($cuti) {
                return [
                    'id' => $cuti->id,
                    'jenis_cuti' => $cuti->jenisCuti?->nama_cuti,
                    'tgl_mulai' => $cuti->tgl_mulai,
                    'tgl_selesai' => $cuti->tgl_selesai,
                    'jumlah_hari' => $cuti->jumlah_hari,
                    'alasan' => $cuti->alasan,
                    'status' => $cuti->status,
                    'lampiran' => $cuti->lampiran_path ? asset('storage/' . $cuti->lampiran_path) : null,
                    'created_at' => $cuti->created_at->toDateTimeString()
                ];
            })
        ], 200);
    }

    /**
     * Submit a new leave request.
     */
    public function submitRequest(Request $request)
    {
        $request->validate([
            'jenis_cuti_id' => 'required|exists:tbl_jenis_cuti,id',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'keterangan' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048'
        ]);

        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        // Calculate days (simple calculation, doesn't skip holidays yet depending on logic)
        $tglMulai = Carbon::parse($request->tgl_mulai);
        $tglSelesai = Carbon::parse($request->tgl_selesai);
        $jumlahHari = $tglMulai->diffInDays($tglSelesai) + 1;

        $lampiranPath = null;
        if ($request->hasFile('attachment')) {
            $lampiranPath = $request->file('attachment')->store('cuti/' . date('Y/m'), 'public');
        }

        $pengajuan = PengajuanCuti::create([
            'id_karyawan' => $karyawan->id,
            'id_jenis_cuti' => $request->jenis_cuti_id,
            'tgl_mulai' => $request->tgl_mulai,
            'tgl_selesai' => $request->tgl_selesai,
            'jumlah_hari' => $jumlahHari,
            'alasan' => $request->keterangan,
            'lampiran_path' => $lampiranPath,
            'status' => 'Pending'
        ]);

        return response()->json([
            'message' => 'Pengajuan cuti berhasil dibuat',
            'data' => [
                'id' => $pengajuan->id,
                'status' => $pengajuan->status
            ]
        ], 201);
    }

    /**
     * Get pending approvals (For Managers) - filtered by same departemen.
     */
    public function pendingApprovals(Request $request)
    {
        $user = $request->user();
        $karyawan = $user->karyawan;

        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        // Only managers / HR Staff can access approvals
        if (!$user->hasAnyRole(['Manajer Departemen', 'HR Manager', 'HR Staff', 'Super Admin'])) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk melihat data ini.'], 403);
        }

        $query = PengajuanCuti::with(['karyawan', 'jenisCuti'])
            ->where('status', 'Pending');

        // Manajer Departemen hanya melihat bawahan di departemennya
        if ($user->hasRole('Manajer Departemen') && $karyawan->id_departemen) {
            $query->whereHas('karyawan', function ($q) use ($karyawan) {
                $q->where('id_departemen', $karyawan->id_departemen);
            });
        }

        $approvals = $query->orderBy('created_at', 'asc')->get();

        return response()->json([
            'data' => $approvals->map(function ($cuti) {
                return [
                    'id'           => $cuti->id,
                    'nama_karyawan' => trim($cuti->karyawan?->first_name . ' ' . $cuti->karyawan?->last_name),
                    'nip'          => $cuti->karyawan?->nip,
                    'departemen'   => $cuti->karyawan?->departemen?->nama_departemen,
                    'jenis_cuti'   => $cuti->jenisCuti?->nama_cuti,
                    'tgl_mulai'    => $cuti->tgl_mulai,
                    'tgl_selesai'  => $cuti->tgl_selesai,
                    'jumlah_hari'  => $cuti->jumlah_hari,
                    'alasan'       => $cuti->alasan,
                    'lampiran'     => $cuti->lampiran_path ? asset('storage/' . $cuti->lampiran_path) : null,
                    'status'       => $cuti->status,
                    'created_at'   => $cuti->created_at?->toDateTimeString(),
                ];
            })
        ], 200);
    }

    /**
     * Approve or Reject a leave request.
     */
    public function approveRequest(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected',
            'catatan' => 'nullable|string'
        ]);

        $pengajuan = PengajuanCuti::find($id);
        if (!$pengajuan) {
            return response()->json(['message' => 'Pengajuan tidak ditemukan.'], 404);
        }

        if ($pengajuan->status !== 'Pending') {
            return response()->json(['message' => 'Pengajuan ini sudah diproses.'], 422);
        }

        $pengajuan->update([
            'status' => $request->status,
            'catatan_approval' => $request->catatan,
            'id_approver' => $request->user()->karyawan?->id
        ]);

        // Jika approved, update saldo cuti
        if ($request->status === 'Approved') {
            $saldo = SaldoCuti::where('id_karyawan', $pengajuan->id_karyawan)
                ->where('id_jenis_cuti', $pengajuan->id_jenis_cuti)
                ->where('tahun_periode', date('Y', strtotime($pengajuan->tgl_mulai)))
                ->first();

            if ($saldo) {
                $saldo->update([
                    'saldo_terpakai' => $saldo->saldo_terpakai + $pengajuan->jumlah_hari,
                    'saldo_akhir' => $saldo->saldo_akhir - $pengajuan->jumlah_hari,
                ]);
            }
        }

        return response()->json([
            'message' => 'Pengajuan berhasil ' . strtolower($request->status)
        ], 200);
    }
}
