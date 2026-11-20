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

        $corrections = KoreksiAbsensi::with(['approver'])
            ->where('id_karyawan', $karyawan->id)
            ->latest()
            ->paginate(20);

        $items = collect($corrections->items())->map(function ($c) {
            return [
                'id' => $c->id,
                'tanggal' => $c->tanggal ? \Carbon\Carbon::parse($c->tanggal)->format('Y-m-d') : null,
                'waktu_masuk_baru' => $c->waktu_masuk_baru ? substr($c->waktu_masuk_baru, 0, 5) : null,
                'waktu_keluar_baru' => $c->waktu_keluar_baru ? substr($c->waktu_keluar_baru, 0, 5) : null,
                'alasan' => $c->alasan,
                'status' => $c->status,
                'catatan_approver' => $c->catatan_approver,
                'approved_by_name' => $c->approver?->name,
                'approved_at' => $c->approved_at ? \Carbon\Carbon::parse($c->approved_at)->format('d M Y H:i') : null,
                'created_at' => $c->created_at ? $c->created_at->format('d M Y H:i') : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $items,
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
            'waktu_masuk_baru' => 'nullable|string',
            'waktu_keluar_baru' => 'nullable|string',
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

        $managerUser = $karyawan->departemen?->kepala?->user;
        if ($managerUser && $managerUser->id !== $request->user()->id) {
            $managerUser->notify(new \App\Notifications\EssSubmissionNotification(
                'attendance',
                'Pengajuan Koreksi Absensi Baru',
                "{$karyawan->nama_lengkap} mengajukan koreksi absensi tanggal {$validated['tanggal']}.",
                '/admin/absensi/rekap?tab=koreksi'
            ));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan koreksi absensi berhasil dikirim.',
            'data' => $correction,
        ], 201);
    }

    public function actionAttendanceCorrection(Request $request, $id)
    {
        $user = $request->user();
        $koreksi = KoreksiAbsensi::with('karyawan.departemen')->findOrFail($id);

        if ($user->karyawan && $koreksi->id_karyawan === $user->karyawan->id) {
            return response()->json(['message' => 'Anda tidak dapat memproses koreksi absensi milik sendiri.'], 403);
        }

        $isHrOrDirector = $user->hasAnyRole(array_merge(\App\Enums\Role::hrRoles(), [\App\Enums\Role::DIREKTUR->value]));
        $isDeptHead = $user->karyawan && $koreksi->karyawan?->departemen?->id_karyawan_kepala === $user->karyawan->id;

        if (!$isHrOrDirector && !$isDeptHead) {
            return response()->json(['message' => 'Anda tidak berwenang memproses koreksi absensi ini.'], 403);
        }

        if ($koreksi->status !== 'Pending') {
            return response()->json(['message' => 'Koreksi absensi sudah diproses sebelumnya.'], 422);
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'catatan' => 'nullable|string|max:500',
        ]);

        $isApprove = $validated['action'] === 'approve';

        \Illuminate\Support\Facades\DB::transaction(function () use ($koreksi, $user, $validated, $isApprove) {
            $locked = KoreksiAbsensi::whereKey($koreksi->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'Pending') {
                abort(422, 'Koreksi absensi sudah diproses sebelumnya.');
            }

            if ($isApprove) {
                $absensi = Absensi::firstOrNew([
                    'id_karyawan' => $locked->id_karyawan,
                    'tanggal' => $locked->tanggal,
                ]);

                if ($locked->waktu_masuk_baru) {
                    $absensi->waktu_masuk = $locked->waktu_masuk_baru;
                }
                if ($locked->waktu_keluar_baru) {
                    $absensi->waktu_keluar = $locked->waktu_keluar_baru;
                }
                $absensi->status_kehadiran = 'Hadir';
                $absensi->catatan = ($absensi->catatan ? $absensi->catatan . ' | ' : '') . 'Koreksi Disetujui: ' . $locked->alasan;
                $absensi->save();

                $locked->id_absensi = $absensi->id;
                $locked->status = 'Approved';
            } else {
                $locked->status = 'Rejected';
            }

            $locked->approved_by = $user->id;
            $locked->approved_at = now();
            $locked->catatan_approver = $validated['catatan'] ?? null;
            $locked->save();
        });

        $koreksi->refresh();

        $targetUser = $koreksi->karyawan?->user;
        if ($targetUser) {
            $statusText = $isApprove ? 'Disetujui' : 'Ditolak';
            $targetUser->notify(new \App\Notifications\EssSubmissionNotification(
                'attendance',
                "Pengajuan Koreksi Absensi {$statusText}",
                "Pengajuan koreksi absensi tanggal " . \Carbon\Carbon::parse($koreksi->tanggal)->format('d/m/Y') . " telah {$statusText}." . (!empty($validated['catatan']) ? " Catatan: {$validated['catatan']}" : ''),
                '/ess?tab=koreksi'
            ));
        }

        return response()->json([
            'status' => 'success',
            'message' => "Pengajuan koreksi absensi berhasil " . ($isApprove ? 'disetujui.' : 'ditolak.'),
            'data' => $koreksi,
        ]);
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

        if (\App\Models\ApprovalRule::where('tipe', 'DinasLuar')->where('id_departemen', $karyawan->id_departemen)->exists()) {
            app(\App\Services\ApprovalService::class)->initApproval($trip);
        }

        $managerUser = $karyawan->departemen?->kepala?->user;
        if ($managerUser && $managerUser->id !== $request->user()->id) {
            $managerUser->notify(new \App\Notifications\EssSubmissionNotification(
                'attendance',
                'Pengajuan Dinas Luar Baru',
                "{$karyawan->nama_lengkap} mengajukan dinas luar ke {$validated['lokasi_tujuan']}.",
                '/ess?tab=dinas'
            ));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan dinas luar berhasil didaftarkan.',
            'data' => $trip->fresh(),
        ], 201);
    }

    public function actionBusinessTrip(Request $request, $id)
    {
        $user = $request->user();
        $actorKaryawan = $user->karyawan;

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'catatan' => 'nullable|string|max:500',
        ]);

        $trip = DinasLuar::with('karyawan')->findOrFail($id);

        if ($actorKaryawan && (int) $trip->id_karyawan === (int) $actorKaryawan->id) {
            return response()->json(['message' => 'Anda tidak dapat menyetujui atau menolak dinas luar Anda sendiri.'], 403);
        }

        $isHrOrDirector = $user->hasAnyRole(array_merge(\App\Enums\Role::hrRoles(), [\App\Enums\Role::DIREKTUR->value]));
        $isDeptHead = $actorKaryawan && $trip->karyawan && \App\Models\Departemen::where('id', $trip->karyawan->id_departemen)
            ->where('id_karyawan_kepala', $actorKaryawan->id)
            ->exists();

        if (!$isHrOrDirector && !$isDeptHead) {
            return response()->json(['message' => 'Anda tidak memiliki wewenang memproses pengajuan dinas luar ini.'], 403);
        }

        $hasWorkflow = \App\Models\ApprovalProcess::forDocument('DinasLuar', $trip->id)->where('status', 'Pending')->exists();
        if ($hasWorkflow && $actorKaryawan) {
            $approvalService = app(\App\Services\ApprovalService::class);
            if ($validated['action'] === 'approve') {
                $approvalService->approve($trip, $actorKaryawan->id, $validated['catatan'] ?? null);
            } else {
                $approvalService->reject($trip, $actorKaryawan->id, $validated['catatan'] ?? 'Ditolak');
            }
            $trip->refresh();
        } else {
            \Illuminate\Support\Facades\DB::transaction(function () use ($trip, $validated) {
                $locked = DinasLuar::where('id', $trip->id)->lockForUpdate()->firstOrFail();
                if ($locked->status !== 'Pending') {
                    abort(422, 'Pengajuan dinas luar sudah diproses.');
                }
                $locked->update([
                    'status' => $validated['action'] === 'approve' ? 'Approved' : 'Rejected',
                ]);
            });
            $trip->refresh();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan dinas luar berhasil ' . ($validated['action'] === 'approve' ? 'disetujui.' : 'ditolak.'),
            'data' => $trip,
        ]);
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
                    'honorarium' => (float) $detail->honorarium,
                    'lembur' => (float) $detail->lembur,
                    'total_potongan' => (float) $detail->total_potongan,
                    'gaji_bersih' => (float) $detail->gaji_bersih,
                ],
                'rincian_komponen' => is_array($detail->rincian_komponen)
                    ? $detail->rincian_komponen
                    : json_decode($detail->rincian_komponen, true) ?? [],
            ],
        ]);
    }

    public function downloadPayslipPdf(Request $request, $id)
    {
        $user = $request->user();
        $karyawan = $user->karyawan;

        $detail = PayrollDetail::with(['payroll', 'karyawan.departemen'])->findOrFail($id);

        $isHrOrAdmin = $user->hasAnyRole(array_merge(\App\Enums\Role::hrRoles(), \App\Enums\Role::adminRoles()));
        $isOwner = $karyawan && (int) $detail->id_karyawan === (int) $karyawan->id;

        abort_unless($isHrOrAdmin || $isOwner, 403, 'Anda tidak berwenang mengunduh slip gaji ini.');

        $statusVal = $detail->payroll?->status instanceof \BackedEnum
            ? $detail->payroll->status->value
            : (string) ($detail->payroll?->status ?? 'Draft');

        abort_unless(in_array($statusVal, ['Approved', 'Paid'], true), 403, 'Slip gaji belum dipublikasikan.');

        $settings = \App\Models\Setting::all()->pluck('value', 'key');
        $logoPath = null;
        if (isset($settings['company_logo']) && $settings['company_logo']) {
            $candidate = storage_path('app/public/' . $settings['company_logo']);
            if (file_exists($candidate)) {
                $logoPath = $candidate;
            }
        }

        $company = [
            'name' => $settings['company_name'] ?? 'PERSIJA JAYA JAKARTA',
            'address' => $settings['company_address'] ?? 'Rasuna Office Park, Kuningan, Jakarta Selatan',
            'logo_path' => $logoPath,
        ];

        $rincian = $detail->rincian_komponen;
        if (is_string($rincian)) {
            $rincian = json_decode($rincian, true);
        }
        $rincian = is_array($rincian) ? $rincian : [];

        $pendapatanTambahan = 0;
        foreach ($rincian as $item) {
            if (isset($item['amount']) && (float) $item['amount'] > 0) {
                $pendapatanTambahan += (float) $item['amount'];
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.payroll.payslip', [
            'payroll' => $detail->payroll,
            'detail' => $detail,
            'karyawan' => $detail->karyawan,
            'rincian' => $rincian,
            'company' => $company,
            'tgl_cetak' => now(),
            'pendapatan_tambahan' => $pendapatanTambahan,
        ]);

        $pdf->setPaper('a5', 'portrait');

        $safePeriod = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($detail->payroll?->bulan_periode ?? 'periode'));
        return $pdf->download("Slip_Gaji_{$safePeriod}.pdf");
    }

    /**
     * ─── 4. REIMBURSEMENT (1 JALUR TRACKING DARI PENGAJUAN DANA) ────────────
     */

    public function getReimbursements(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 404);
        }

        $pengajuanItems = \App\Models\PengajuanHeader::with(['departemen', 'pembayaran'])
            ->where(function ($q) use ($karyawan) {
                $q->where('id_pengaju', $karyawan->id)
                    ->orWhere('id_karyawan_penerima', $karyawan->id);
            })
            ->where('tipe_pengajuan', \App\Enums\PengajuanType::REIMBURSE)
            ->latest('tgl_pengajuan')
            ->get();

        $items = $pengajuanItems->map(function ($p) {
            $statusVal = $p->status_global instanceof \BackedEnum
                ? $p->status_global->value
                : (string) $p->status_global;

            $hasPaidRecord = $p->pembayaran->isNotEmpty() || $statusVal === \App\Enums\PengajuanStatus::PAID->value;
            $paymentStatus = match (true) {
                $hasPaidRecord => 'Paid',
                $statusVal === \App\Enums\PengajuanStatus::APPROVED->value => 'Waiting Payment',
                $statusVal === \App\Enums\PengajuanStatus::REJECTED->value => 'Rejected',
                default => 'Pending',
            };

            $latestPay = $p->pembayaran->first();
            $paidAt = $latestPay?->tgl_bayar
                ? \Carbon\Carbon::parse($latestPay->tgl_bayar)->format('Y-m-d')
                : null;

            return [
                'id' => $p->id,
                'nomor_reimbursement' => $p->nomor_pengajuan,
                'judul_klaim' => $p->judul_pengajuan,
                'kategori' => 'Pengajuan Dana (Reimburse)',
                'tgl_transaksi' => $p->tgl_pengajuan ? \Carbon\Carbon::parse($p->tgl_pengajuan)->format('Y-m-d') : '',
                'total_biaya' => (float) $p->total_nominal_diajukan,
                'keterangan' => $p->catatan_header,
                'status' => $statusVal,
                'payment_status' => $paymentStatus,
                'paid_at' => $paidAt,
                'departemen' => $p->departemen?->nama_departemen,
            ];
        })->values()->all();

        $legacyClaims = Reimbursement::where('id_karyawan', $karyawan->id)
            ->latest('tgl_transaksi')
            ->get();

        foreach ($legacyClaims as $c) {
            $isLinked = $pengajuanItems->contains(function ($p) use ($c) {
                $catatan = (string) ($p->catatan_header ?? '');
                if (str_contains($catatan, (string) $c->nomor_reimbursement)) {
                    return true;
                }

                $sameTitle = $p->judul_pengajuan === 'Reimbursement: ' . $c->judul_klaim;
                $sameAmount = round((float) $p->total_nominal_diajukan, 2) === round((float) $c->total_biaya, 2);
                $claimDate = $c->tgl_transaksi ? \Carbon\Carbon::parse($c->tgl_transaksi)->format('Y-m-d') : '';
                $headerDate = $p->tgl_pengajuan ? \Carbon\Carbon::parse($p->tgl_pengajuan)->format('Y-m-d') : '';

                return $sameTitle && $sameAmount && $claimDate === $headerDate;
            });

            if ($isLinked) {
                continue;
            }
            $items[] = [
                'id' => $c->id,
                'nomor_reimbursement' => $c->nomor_reimbursement,
                'judul_klaim' => $c->judul_klaim,
                'kategori' => $c->kategori,
                'tgl_transaksi' => (string) $c->tgl_transaksi,
                'total_biaya' => (float) $c->total_biaya,
                'keterangan' => $c->keterangan,
                'status' => $c->status,
                'payment_status' => strtolower((string) $c->status) === 'paid' ? 'Paid' : (string) $c->status,
                'paid_at' => null,
                'departemen' => null,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'total' => count($items),
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

        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($karyawan, $validated) {
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

            $pengajuan = null;
            if ($karyawan->id_departemen) {
                $pengajuan = \App\Models\PengajuanHeader::create([
                    'nomor_pengajuan' => \App\Services\DocumentNumberService::pengajuan(),
                    'id_pengaju' => $karyawan->id,
                    'id_departemen' => $karyawan->id_departemen,
                    'tgl_pengajuan' => $validated['tgl_transaksi'],
                    'judul_pengajuan' => 'Reimbursement: ' . $validated['judul_klaim'],
                    'tipe_pengajuan' => \App\Enums\PengajuanType::REIMBURSE,
                    'total_nominal_diajukan' => $validated['total_biaya'],
                    'metode_pembayaran' => \App\Enums\PaymentMethod::TRANSFER,
                    'sub_tipe_penerima' => 'Karyawan',
                    'id_karyawan_penerima' => $karyawan->id,
                    'catatan_header' => $validated['keterangan'] ?? "Sumber ESS Reimbursement {$claim->nomor_reimbursement}",
                    'status_global' => \App\Enums\PengajuanStatus::PENDING_APPROVAL,
                ]);
            }

            return ['claim' => $claim, 'pengajuan' => $pengajuan];
        });
        $claim = $result['claim'];

        $managerUser = $karyawan->departemen?->kepala?->user;
        if ($managerUser && $managerUser->id !== $request->user()->id) {
            $managerUser->notify(new \App\Notifications\EssSubmissionNotification(
                'ess',
                'Pengajuan Reimbursement Baru',
                "{$karyawan->nama_lengkap} mengajukan reimbursement: {$validated['judul_klaim']}.",
                '/ess?tab=reimbursement'
            ));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan reimbursement berhasil didaftarkan.',
            'data' => $claim,
        ], 201);
    }

    /**
     * ─── 5. KONTRAK INFO ───────────────────────────────────────────────────
     */
    public function getKontrakInfo(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 404);
        }

        $karyawan->load(['departemen', 'latestRiwayatKarir', 'contracts' => fn($q) => $q->where('status', 'active')->latest()->limit(1)]);

        $latestContract = $karyawan->contracts->first();
        $tanggalMulai   = $karyawan->latestRiwayatKarir?->tanggal_efektif;
        $tanggalBerakhir = $karyawan->latestRiwayatKarir?->tanggal_berakhir_kontrak;

        $sisaHari = null;
        $isExpiringSoon = false;
        if ($tanggalBerakhir) {
            $berakhir = \Carbon\Carbon::parse($tanggalBerakhir);
            $sisaHari = (int) now()->startOfDay()->diffInDays($berakhir->startOfDay(), false);
            $isExpiringSoon = $sisaHari !== null && $sisaHari <= 30 && $sisaHari >= 0;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'nip'               => $karyawan->nomor_induk_karyawan,
                'nama_lengkap'      => $karyawan->nama_lengkap,
                'jabatan'           => $karyawan->jabatan,
                'departemen'        => $karyawan->departemen?->nama_departemen ?? '-',
                'status_karyawan'   => $karyawan->status_karyawan ?? '-',
                'contract_type'     => $karyawan->contract_type ?? '-',
                'nomor_kontrak'     => $latestContract?->nomor_kontrak ?? $karyawan->nomor_kontrak ?? '-',
                'versi_kontrak'     => $latestContract ? 'v' . $latestContract->version_number : null,
                'tanggal_mulai'     => $tanggalMulai ? \Carbon\Carbon::parse($tanggalMulai)->format('Y-m-d') : null,
                'tanggal_berakhir'  => $tanggalBerakhir ? \Carbon\Carbon::parse($tanggalBerakhir)->format('Y-m-d') : null,
                'sisa_hari'         => $sisaHari,
                'is_expiring_soon'  => $isExpiringSoon,
                'tgl_bergabung'     => $karyawan->tgl_bergabung ? \Carbon\Carbon::parse($karyawan->tgl_bergabung)->format('Y-m-d') : null,
                'no_rekening'       => $karyawan->no_rekening ?? null,
                'nama_bank'         => $karyawan->nama_bank ?? null,
            ],
        ]);
    }

    /**
     * ─── 6. RIWAYAT KARIR ─────────────────────────────────────────────────
     */
    public function getRiwayatKarir(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 404);
        }

        $riwayat = $karyawan->riwayatKarir()
            ->with('departemen')
            ->orderByDesc('tanggal_efektif')
            ->get()
            ->map(fn ($r) => [
                'id'                     => $r->id,
                'jabatan'                => $r->jabatan ?? '-',
                'departemen'             => $r->departemen?->nama_departemen ?? '-',
                'jenis_perubahan'        => $r->tipe_peristiwa ?? '-',
                'catatan'                => $r->catatan ?? null,
                'tanggal_mulai_kontrak'  => $r->tanggal_efektif
                    ? \Carbon\Carbon::parse($r->tanggal_efektif)->format('Y-m-d') : null,
                'tanggal_berakhir_kontrak' => $r->tanggal_berakhir_kontrak
                    ? \Carbon\Carbon::parse($r->tanggal_berakhir_kontrak)->format('Y-m-d') : null,
                'created_at'             => $r->created_at?->format('Y-m-d'),
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => $riwayat,
        ]);
    }

    /**
     * ─── 7. DOKUMEN KARYAWAN ───────────────────────────────────────────────
     */
    public function getDokumen(Request $request)
    {
        $karyawan = $request->user()->karyawan;
        if (!$karyawan) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 404);
        }

        $dokumen = $karyawan->dokumen()
            ->latest()
            ->get()
            ->map(fn ($d) => [
                'id'           => $d->id,
                'nama_dokumen' => $d->judul_dokumen,
                'kategori'     => $d->kategori_dokumen ?? 'Lainnya',
                'file_path'    => $d->file_path ? url("/api/v1/ess/dokumen/{$d->id}/download") : null,
                'uploaded_at'  => $d->created_at?->format('Y-m-d'),
                'keterangan'   => $d->catatan ?? null,
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => $dokumen,
        ]);
    }

    public function downloadDokumen(Request $request, $id)
    {
        $user = $request->user();
        $karyawan = $user->karyawan;

        $dokumen = \App\Models\KaryawanDokumen::findOrFail($id);

        $isHrOrAdmin = $user->hasAnyRole(array_merge(\App\Enums\Role::hrRoles(), \App\Enums\Role::adminRoles()));
        $isOwner = $karyawan && (int) $dokumen->id_karyawan === (int) $karyawan->id;

        abort_unless($isHrOrAdmin || $isOwner, 403, 'Anda tidak berwenang mengunduh dokumen ini.');

        $path = $dokumen->file_path;
        abort_if(!$path || str_contains($path, '..'), 404);

        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            return \Illuminate\Support\Facades\Storage::disk('local')->download($path, basename($path));
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->download($path, basename($path));
        }

        abort(404, 'Berkas dokumen tidak ditemukan.');
    }
}
