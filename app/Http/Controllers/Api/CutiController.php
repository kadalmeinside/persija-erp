<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SaldoCuti;
use App\Models\PengajuanCuti;
use App\Models\HariLibur;
use App\Models\JenisCuti;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\CarbonPeriod;
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
                    'bisa_mundur' => (bool) $j->bisa_mundur,
                    'khusus_perempuan' => (bool) $j->khusus_perempuan,
                    'is_unlimited' => (bool) $j->is_unlimited,
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
                    'is_unlimited' => (bool) $saldo->jenisCuti?->is_unlimited,
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
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        $jenisCuti = JenisCuti::findOrFail($request->jenis_cuti_id);
        if ($jenisCuti->wajib_lampiran && !$request->hasFile('attachment')) {
            return response()->json(['message' => 'Lampiran wajib untuk jenis cuti ini.'], 422);
        }

        if (!$jenisCuti->bisa_mundur && Carbon::parse($request->tgl_mulai)->isBefore(Carbon::today())) {
            return response()->json(['message' => 'Jenis cuti ini tidak dapat diajukan untuk tanggal lampau.'], 422);
        }

        if ($jenisCuti->khusus_perempuan && $karyawan->jenis_kelamin !== 'P') {
            return response()->json(['message' => 'Jenis cuti ini hanya diperuntukkan bagi karyawan perempuan.'], 422);
        }

        $tglMulai = Carbon::parse($request->tgl_mulai);
        $tglSelesai = Carbon::parse($request->tgl_selesai);
        $holidays = HariLibur::whereBetween('tanggal', [
            $tglMulai->toDateString(),
            $tglSelesai->toDateString(),
        ])->pluck('tanggal')->map(fn ($date) => Carbon::parse($date)->toDateString())->flip();

        $jumlahHari = 0;
        foreach (CarbonPeriod::create($tglMulai, $tglSelesai) as $date) {
            if (!$date->isWeekend() && !$holidays->has($date->toDateString())) {
                $jumlahHari++;
            }
        }

        if ($jumlahHari === 0) {
            return response()->json(['message' => 'Durasi cuti efektif adalah 0 hari.'], 422);
        }

        $hasOverlap = PengajuanCuti::where('id_karyawan', $karyawan->id)
            ->whereIn('status', ['Pending', 'Pending Approval', 'Approved'])
            ->where(function ($query) use ($tglMulai, $tglSelesai) {
                $query->where('tgl_mulai', '<=', $tglSelesai)
                    ->where('tgl_selesai', '>=', $tglMulai);
            })
            ->exists();

        if ($hasOverlap) {
            return response()->json(['message' => 'Tanggal cuti bertumpang tindih dengan pengajuan yang sudah ada.'], 422);
        }

        $tahunPeriode = $tglMulai->year;
        $lampiranPath = null;

        try {
            $pengajuan = DB::transaction(function () use (
                $request,
                $karyawan,
                $jenisCuti,
                $tglMulai,
                $tglSelesai,
                $jumlahHari,
                $tahunPeriode,
                &$lampiranPath
            ) {
                $saldo = null;
                if (!$jenisCuti->is_unlimited) {
                    $saldo = SaldoCuti::where([
                        'id_karyawan' => $karyawan->id,
                        'id_jenis_cuti' => $jenisCuti->id,
                        'tahun_periode' => $tahunPeriode,
                    ])->lockForUpdate()->first();

                    if (!$saldo) {
                        throw new \DomainException('Saldo cuti belum dibuat untuk jenis cuti ini.');
                    }

                    $available = $saldo->saldo_awal - $saldo->saldo_terpakai;
                    if ($available < $jumlahHari) {
                        throw new \DomainException("Saldo cuti tidak mencukupi. Sisa: {$available} hari, diajukan: {$jumlahHari} hari.");
                    }
                }

                if ($request->hasFile('attachment')) {
                    $lampiranPath = $request->file('attachment')->store('cuti/' . now()->format('Y/m'), 'public');
                }

                $pengajuan = PengajuanCuti::create([
                    'id_karyawan' => $karyawan->id,
                    'id_jenis_cuti' => $jenisCuti->id,
                    'tgl_mulai' => $tglMulai->toDateString(),
                    'tgl_selesai' => $tglSelesai->toDateString(),
                    'jumlah_hari' => $jumlahHari,
                    'alasan' => $request->keterangan,
                    'lampiran_path' => $lampiranPath,
                    'status' => 'Pending',
                ]);

                if ($saldo) {
                    $saldo->increment('saldo_terpakai', $jumlahHari);
                    $saldo->decrement('saldo_akhir', $jumlahHari);
                }

                app(\App\Services\ApprovalService::class)->initApproval($pengajuan);

                return $pengajuan;
            });
        } catch (\DomainException $e) {
            if ($lampiranPath) {
                Storage::disk('public')->delete($lampiranPath);
            }

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            if ($lampiranPath) {
                Storage::disk('public')->delete($lampiranPath);
            }

            Log::error('Gagal membuat pengajuan cuti dari API.', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Pengajuan cuti gagal diproses. Silakan coba lagi.'], 500);
        }

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
        $karyawan = $request->user()->karyawan;

        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        // Ambil data dari ApprovalProcess yang menunggu tindakan (target) karyawan ini
        $approvals = \App\Models\ApprovalProcess::with(['cuti.karyawan.departemen', 'cuti.jenisCuti'])
            ->where('id_karyawan_target', $karyawan->id)
            ->where('status', 'Pending')
            ->whereNotNull('id_cuti')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'data' => $approvals->map(function ($process) {
                $cuti = $process->cuti;
                return [
                    'id_approval'  => $process->id,
                    'id_pengajuan' => $cuti->id,
                    'nama_karyawan'=> $cuti->karyawan?->nama_lengkap,
                    'nip'          => $cuti->karyawan?->nomor_induk_karyawan,
                    'departemen'   => $cuti->karyawan?->departemen?->nama_departemen,
                    'jenis_cuti'   => $cuti->jenisCuti?->nama_cuti,
                    'tgl_mulai'    => $cuti->tgl_mulai,
                    'tgl_selesai'  => $cuti->tgl_selesai,
                    'jumlah_hari'  => $cuti->jumlah_hari,
                    'alasan'       => $cuti->alasan,
                    'lampiran'     => $cuti->lampiran_path ? asset('storage/' . $cuti->lampiran_path) : null,
                    'status'       => $process->status,
                    'created_at'   => $cuti->created_at?->toDateTimeString(),
                    'level_order'  => $process->level_order,
                    'label_aksi'   => $process->label_aksi,
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
            'catatan' => 'nullable|string',
            'pin' => 'required|string|size:6',
        ]);

        $user = $request->user();
        if (!$user->pin || !Hash::check($request->pin, $user->pin)) {
            return response()->json(['message' => 'PIN salah atau belum diatur.'], 422);
        }

        $pengajuan = PengajuanCuti::find($id);
        if (!$pengajuan) {
            return response()->json(['message' => 'Pengajuan tidak ditemukan.'], 404);
        }

        $karyawanIdAction = $user->karyawan?->id;
        if (!$karyawanIdAction) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }
        $approvalService = app(\App\Services\ApprovalService::class);

        try {
            if ($request->status === 'Approved') {
                $approvalService->approve($pengajuan, $karyawanIdAction, $request->catatan);
            } else {
                DB::transaction(function () use ($approvalService, $pengajuan, $karyawanIdAction, $request) {
                    $saldo = SaldoCuti::where([
                        'id_karyawan' => $pengajuan->id_karyawan,
                        'id_jenis_cuti' => $pengajuan->id_jenis_cuti,
                        'tahun_periode' => Carbon::parse($pengajuan->tgl_mulai)->year,
                    ])->lockForUpdate()->first();

                    if ($saldo && !$pengajuan->jenisCuti->is_unlimited) {
                        $saldo->decrement('saldo_terpakai', $pengajuan->jumlah_hari);
                        $saldo->increment('saldo_akhir', $pengajuan->jumlah_hari);
                    }

                    $approvalService->reject($pengajuan, $karyawanIdAction, $request->catatan);
                });
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Persetujuan berhasil diproses.'
        ], 200);
    }

    public function cancelRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 403);
        }

        try {
            DB::transaction(function () use ($request, $id, $karyawan, $validated) {
                $cuti = PengajuanCuti::where('id', $id)
                    ->where('id_karyawan', $karyawan->id)
                    ->lockForUpdate()
                    ->first();
                if (!$cuti) {
                    throw new \DomainException('Pengajuan cuti tidak ditemukan.');
                }
                if (!in_array($cuti->status, ['Pending', 'Approved'], true)) {
                    throw new \DomainException('Pengajuan cuti tidak dapat dibatalkan pada status ini.');
                }
                if (Carbon::parse($cuti->tgl_mulai)->isPast()) {
                    throw new \DomainException('Cuti yang sudah dimulai tidak dapat dibatalkan.');
                }

                $saldo = SaldoCuti::where([
                    'id_karyawan' => $cuti->id_karyawan,
                    'id_jenis_cuti' => $cuti->id_jenis_cuti,
                    'tahun_periode' => Carbon::parse($cuti->tgl_mulai)->year,
                ])->lockForUpdate()->first();
                if ($saldo && !$cuti->jenisCuti->is_unlimited) {
                    $saldo->decrement('saldo_terpakai', $cuti->jumlah_hari);
                    $saldo->increment('saldo_akhir', $cuti->jumlah_hari);
                }

                $cuti->update([
                    'status' => 'Cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => $request->user()->id,
                    'cancellation_reason' => $validated['reason'],
                ]);
            });
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Pengajuan cuti berhasil dibatalkan.']);
    }
}
