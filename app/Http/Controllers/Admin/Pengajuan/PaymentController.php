<?php

namespace App\Http\Controllers\Admin\Pengajuan;

use App\Http\Controllers\Controller;
use App\Models\PengajuanHeader;
use App\Models\PengajuanPembayaran;
use App\Models\KasBank;
use App\Enums\PengajuanStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * PAGE: Payment Schedule (Rencana Pembayaran).
     */
    public function schedule(Request $request)
    {
        // Ambil filter status dari request, default ke ['Approved']
        $statuses = $request->input('statuses', [PengajuanStatus::APPROVED]);
        if (!is_array($statuses)) {
            $statuses = [$statuses];
        }

        $query = PengajuanHeader::with(['pengaju:id,nama_lengkap', 'vendorPenerima:id,nama_vendor', 'karyawanPenerima:id,nama_lengkap'])
                    ->withSum('pembayaran as total_dibayar', 'nominal_bayar') // ✅ Single JOIN — tidak ada N+1
                    ->whereIn('status_global', $statuses)
                    ->whereRaw('(total_nominal_diajukan - COALESCE((SELECT SUM(nominal_bayar) FROM tbl_pengajuan_pembayaran WHERE id_pengajuan = tbl_pengajuan_header.id), 0)) > 0');

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nomor_pengajuan', 'like', '%' . $request->search . '%')
                  ->orWhereHas('pengaju', function($q2) use ($request) {
                      $q2->where('nama_lengkap', 'like', '%' . $request->search . '%');
                  })
                  ->orWhereHas('vendorPenerima', function($q3) use ($request) {
                      $q3->where('nama_vendor', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // ✅ Hitung sisa_tagihan dari data yang sudah di-aggregate (tidak ada query tambahan)
        $items = $query->orderBy('tgl_pengajuan', 'asc')
                       ->get()
                       ->each(function ($item) {
                            $item->sisa_tagihan = $item->total_nominal_diajukan - ($item->total_dibayar ?? 0);
                       });

        return Inertia::render('Admin/Pengajuan/PaymentSchedule', [
            'items' => $items,
            'filters' => [
                'search' => $request->search ?? '',
                'statuses' => $statuses
            ]
        ]);
    }

    /**
     * ACTION: Print Payment Schedule PDF.
     */
    public function printSchedule(Request $request)
    {
        $request->validate([
            'items'              => 'required|array',
            'items.*.id'         => 'required|exists:tbl_pengajuan_header,id',
            'items.*.rencana_bayar' => 'required|numeric|min:0',
            'items.*.keterangan' => 'nullable|string'
        ]);

        $ids = collect($request->items)->pluck('id');

        // ✅ Batch load semua pengajuan sekaligus — tidak ada N+1
        $pengajuanMap = PengajuanHeader::with(['pengaju:id,nama_lengkap', 'vendorPenerima:id,nama_vendor', 'karyawanPenerima:id,nama_lengkap', 'detail.akunGl:id,kode_akun,nama_akun'])
            ->withSum('pembayaran as total_dibayar', 'nominal_bayar')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $data         = [];
        $totalRencana = 0;

        foreach ($request->items as $reqItem) {
            $pengajuan = $pengajuanMap->get($reqItem['id']);
            if (!$pengajuan) continue;

            $totalBayar = $pengajuan->total_dibayar ?? 0;
            $sisa       = $pengajuan->total_nominal_diajukan - $totalBayar;

            $data[] = [
                'pengajuan'    => $pengajuan,
                'sisa_tagihan' => $sisa,
                'rencana_bayar'=> $reqItem['rencana_bayar'],
                'keterangan'   => $reqItem['keterangan'] ?? '',
                'penerima_nama'=> $pengajuan->vendorPenerima
                    ? $pengajuan->vendorPenerima->nama_vendor
                    : ($pengajuan->karyawanPenerima
                        ? $pengajuan->karyawanPenerima->nama_lengkap
                        : $pengajuan->pengaju->nama_lengkap),
                'penerima_bank'=> $pengajuan->bank_tujuan . ' - ' . $pengajuan->no_rek_tujuan . ' (' . $pengajuan->atas_nama_tujuan . ')',
            ];

            $totalRencana += $reqItem['rencana_bayar'];
        }

        // Fetch Company Settings
        $settings = \App\Models\Setting::all()->pluck('value', 'key');

        $logoPath = null;
        if (isset($settings['company_logo']) && $settings['company_logo']) {
            $logoPath = storage_path('app/public/' . $settings['company_logo']);
            if (!file_exists($logoPath)) $logoPath = null;
        }

        $company = [
            'name'      => $settings['company_name'] ?? 'PERSIJA JAYA JAKARTA',
            'address'   => $settings['company_address'] ?? 'Rasuna Office Park, Kuningan, Jakarta Selatan',
            'logo_path' => $logoPath,
        ];

        $pdf = \PDF::loadView('admin.pengajuan.print-schedule', [
            'data'         => $data,
            'totalRencana' => $totalRencana,
            'tgl_cetak'    => now(),
            'user'         => Auth::user(),
            'company'      => $company
        ]);

        return $pdf->stream('Payment_Schedule_' . now()->format('Ymd_His') . '.pdf');
    }


    /**
     * ACTION: Store Payment (Pay Invoice).
     */
    public function store(Request $request, PengajuanHeader $pengajuan)
    {
        if ($pengajuan->status_global !== PengajuanStatus::APPROVED && $pengajuan->status_global !== PengajuanStatus::PAID) {
             return redirect()->back()->with('error', 'Pengajuan belum disetujui atau status tidak valid.');
        }

        $totalDibayar = $pengajuan->pembayaran()->sum('nominal_bayar');
        $sisaTagihan = $pengajuan->total_nominal_diajukan - $totalDibayar;

        if ($sisaTagihan <= 0) {
            return redirect()->back()->with('error', 'Tagihan sudah lunas.');
        }

        $request->validate([
            'tgl_bayar' => 'required|date',
            'nominal_bayar' => 'required|numeric|min:1|max:' . $sisaTagihan,
            'id_kas_bank' => 'required|exists:tbl_kas_bank,id',
            'bukti_bayar' => 'required|file|mimes:jpg,png,jpeg,pdf|max:2048',
            'catatan' => 'nullable|string'
        ]);

        try {
            DB::transaction(function () use ($request, $pengajuan, $sisaTagihan) {
                $path = $request->file('bukti_bayar')->store('payments', 'public');

                PengajuanPembayaran::create([
                    'id_pengajuan' => $pengajuan->id,
                    'tgl_bayar' => $request->tgl_bayar,
                    'nominal_bayar' => $request->nominal_bayar,
                    'id_kas_bank' => $request->id_kas_bank,
                    'bukti_bayar_path' => $path,
                    'catatan' => $request->catatan,
                    'created_by' => Auth::id()
                ]);

                if (round($request->nominal_bayar) >= round($sisaTagihan)) {
                    if ($pengajuan->laporanPenggunaan) {
                        $pengajuan->update(['status_global' => PengajuanStatus::SETTLED]);
                    } else {
                        $pengajuan->update(['status_global' => PengajuanStatus::PAID]);
                    }
                }
                
                // GL Logic (Simplified for brevity, ideally in GLService)
                $this->createJournal($pengajuan, $request);
            });

            return redirect()->back()->with('success', 'Pembayaran berhasil disimpan.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal bayar: ' . $e->getMessage());
        }
    }

    private function createJournal($pengajuan, $request)
    {
        $accountPayable = \App\Models\AkunGl::where('kode_akun', '21000')->first();

        $details    = [];
        $totalDebit = 0;

        // ✅ Pastikan detail sudah ter-load (hindari lazy load dalam loop)
        if (!$pengajuan->relationLoaded('detail')) {
            $pengajuan->load('detail');
        }

        // DEBIT SIDE (Expense Accounts)
        foreach ($pengajuan->detail as $item) {
            $nominal    = $item->nominal_item;
            $details[]  = [
                'id_akun'    => $item->id_akun,
                'debit'      => $nominal,
                'kredit'     => 0,
                'keterangan' => 'Pembayaran ' . $pengajuan->nomor_pengajuan . ': ' . $item->deskripsi_item
            ];
            $totalDebit += $nominal;
        }

        // CREDIT SIDE (Bank)
        $kasBank = KasBank::find($request->id_kas_bank);
        if (!$kasBank || !$kasBank->id_akun_gl) {
            throw new \Exception('Akun GL untuk Bank ini belum disetting.');
        }

        $details[] = [
            'id_akun'    => $kasBank->id_akun_gl,
            'debit'      => 0,
            'kredit'     => $totalDebit,
            'keterangan' => 'Pengeluaran Bank: ' . $request->catatan
        ];

        \App\Services\GLService::createJournal(
            $request->tgl_bayar,
            'Pembayaran Pengajuan ' . $pengajuan->nomor_pengajuan,
            $details,
            'AP',
            $pengajuan->id,
            'Pengeluaran Kas'
        );
    }
}
