<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KasBank;
use App\Models\PengajuanHeader;
use App\Models\JurnalDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PettyCashReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate   = $request->start_date ?? date('Y-m-01');
        $endDate     = $request->end_date   ?? date('Y-m-t');
        $idKasKecil  = $request->id_kas_kecil;

        // Daftar semua Kas Kecil (untuk dropdown filter)
        $daftarKasKecil = KasBank::where('is_active', true)
            ->where('nama_bank', 'like', '%Kas Kecil%')
            ->orderBy('nama_bank')
            ->get(['id', 'nama_bank', 'nomor_rekening', 'id_akun_gl']);

        // Auto-pilih yang pertama jika belum dipilih
        if (!$idKasKecil && $daftarKasKecil->isNotEmpty()) {
            $idKasKecil = $daftarKasKecil->first()->id;
        }

        $kasKecilTerpilih = $daftarKasKecil->firstWhere('id', $idKasKecil);

        // Saldo awal GL: total debit - kredit di akun GL ini SEBELUM start_date
        $saldoAwal = 0;
        if ($kasKecilTerpilih && $kasKecilTerpilih->id_akun_gl) {
            $debitSebelum  = JurnalDetail::where('id_akun', $kasKecilTerpilih->id_akun_gl)
                ->whereHas('header', fn($q) => $q->where('tgl_jurnal', '<', $startDate)->where('status', 'Posted'))
                ->sum('debit');
            $kreditSebelum = JurnalDetail::where('id_akun', $kasKecilTerpilih->id_akun_gl)
                ->whereHas('header', fn($q) => $q->where('tgl_jurnal', '<', $startDate)->where('status', 'Posted'))
                ->sum('kredit');
            $saldoAwal = (float) ($debitSebelum - $kreditSebelum);
        }

        // Daftar transaksi Petty Cash dalam periode yang dipilih
        $transaksi = collect();
        if ($idKasKecil) {
            $transaksi = PengajuanHeader::with([
                    'pengaju:id,nama_lengkap',
                    'karyawanPenerima:id,nama_lengkap',
                    'departemen:id,nama_departemen',
                    'detail.akunGl:id,kode_akun,nama_akun',
                    'detail.programKerja:id,nama_program',
                ])
                ->where('tipe_pengajuan', 'PettyCash')
                ->where('id_kas_kecil', $idKasKecil)
                ->where('status_global', 'Approved')
                ->whereBetween('tgl_pengajuan', [$startDate, $endDate])
                ->orderBy('tgl_pengajuan', 'asc')
                ->orderBy('nomor_pengajuan', 'asc')
                ->get()
                ->map(function ($p) {
                    return [
                        'id'                  => $p->id,
                        'nomor_pengajuan'     => $p->nomor_pengajuan,
                        'tgl_pengajuan'       => $p->tgl_pengajuan?->format('Y-m-d'),
                        'judul_pengajuan'     => $p->judul_pengajuan,
                        'nama_pengaju'        => $p->pengaju?->nama_lengkap,
                        'nama_penerima'       => $p->karyawanPenerima?->nama_lengkap,
                        'nama_departemen'     => $p->departemen?->nama_departemen,
                        'total_nominal'       => (float) $p->total_nominal_diajukan,
                        'detail'              => $p->detail->map(fn($d) => [
                            'deskripsi'   => $d->deskripsi_item,
                            'nominal'     => (float) $d->nominal_item,
                            'kode_akun'   => $d->akunGl?->kode_akun,
                            'nama_akun'   => $d->akunGl?->nama_akun,
                            'nama_program'=> $d->programKerja?->nama_program,
                        ]),
                    ];
                });
        }

        // Hitung saldo running
        $saldoBerjalan = $saldoAwal;
        $rows = $transaksi->map(function ($t) use (&$saldoBerjalan) {
            $saldoBerjalan -= $t['total_nominal']; // Petty Cash keluar → saldo berkurang
            return array_merge($t, ['saldo_running' => $saldoBerjalan]);
        });

        // Ringkasan
        $totalPengeluaran = $transaksi->sum('total_nominal');
        $saldoAkhir       = $saldoAwal - $totalPengeluaran;

        // Breakdown per departemen
        $perDepartemen = $transaksi->groupBy('nama_departemen')->map(fn($group, $dept) => [
            'nama_departemen' => $dept ?: 'Tidak Diketahui',
            'total'           => $group->sum('total_nominal'),
            'jumlah_transaksi'=> $group->count(),
        ])->values()->sortByDesc('total')->values();

        // Breakdown per akun GL
        $perAkun = $transaksi->flatMap(fn($t) => $t['detail'])
            ->groupBy('kode_akun')
            ->map(fn($items, $kode) => [
                'kode_akun' => $kode,
                'nama_akun' => $items->first()['nama_akun'],
                'total'     => $items->sum('nominal'),
            ])->values()->sortByDesc('total')->values();

        return Inertia::render('Admin/Finance/PettyCashReport/Index', [
            'filters' => [
                'start_date'  => $startDate,
                'end_date'    => $endDate,
                'id_kas_kecil'=> (int) $idKasKecil,
            ],
            'daftarKasKecil'   => $daftarKasKecil,
            'kasKecilTerpilih' => $kasKecilTerpilih,
            'saldoAwal'        => $saldoAwal,
            'saldoAkhir'       => $saldoAkhir,
            'totalPengeluaran' => $totalPengeluaran,
            'transaksi'        => $rows->values(),
            'perDepartemen'    => $perDepartemen,
            'perAkun'          => $perAkun,
        ]);
    }
}
