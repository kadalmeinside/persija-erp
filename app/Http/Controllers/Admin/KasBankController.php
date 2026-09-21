<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KasBank;
use App\Models\AkunGl;
use App\Models\JurnalHeader;
use App\Models\JurnalDetail;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class KasBankController extends Controller
{
    public function index(Request $request)
    {
        $query = KasBank::with('akunGl');

        if ($request->search) {
            $query->where('nama_bank', 'like', '%' . $request->search . '%')
                  ->orWhere('nomor_rekening', 'like', '%' . $request->search . '%');
        }

        $kasBanks = $query->orderBy('nama_bank')->paginate(10);
        
        // Load Akun GL tipe Aset untuk dropdown (biasanya Kas/Bank adalah Aset)
        $akunGls = AkunGl::where('tipe_akun', 'Aset')->orderBy('kode_akun')->get();

        return Inertia::render('Admin/KasBank/Index', [
            'kasBanks' => $kasBanks,
            'akunGls' => $akunGls,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_bank' => 'required|string|max:100',
            'nomor_rekening' => 'required|string|max:50',
            'atas_nama' => 'required|string|max:100',
            'id_akun_gl' => 'required|exists:tbl_akun_gl,id',
            'saldo_awal' => 'nullable|numeric|min:0',
        ]);

        $kasBank = KasBank::create([
            'nama_bank' => $request->nama_bank,
            'nomor_rekening' => $request->nomor_rekening,
            'atas_nama' => $request->atas_nama,
            'id_akun_gl' => $request->id_akun_gl,
            'saldo_awal' => $request->saldo_awal ?? 0,
            'is_active' => true
        ]);

        $this->handleJournal($kasBank, $request->saldo_awal ?? 0);

        return redirect()->back()->with('success', 'Kas/Bank berhasil ditambahkan.');
    }

    public function update(Request $request, KasBank $kasBank)
    {
        $request->validate([
            'nama_bank' => 'required|string|max:100',
            'nomor_rekening' => 'required|string|max:50',
            'atas_nama' => 'required|string|max:100',
            'id_akun_gl' => 'required|exists:tbl_akun_gl,id',
            'saldo_awal' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $kasBank->update($request->only(['nama_bank', 'nomor_rekening', 'atas_nama', 'id_akun_gl', 'saldo_awal', 'is_active']));

        $this->handleJournal($kasBank, $request->saldo_awal ?? 0);

        return redirect()->back()->with('success', 'Kas/Bank berhasil diperbarui.');
    }

    public function destroy(KasBank $kasBank)
    {
        // Guard 1: Cek apakah digunakan di Pengajuan Pembayaran
        if (\App\Models\PengajuanPembayaran::where('id_kas_bank', $kasBank->id)->exists()) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus Kas/Bank karena sudah digunakan dalam Pembayaran Pengajuan.');
        }

        // Guard 2: Cek apakah digunakan di Internal Transfer
        if (\App\Models\InternalTransfer::where('id_from_kas_bank', $kasBank->id)
            ->orWhere('id_to_kas_bank', $kasBank->id)->exists()) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus Kas/Bank karena terdapat riwayat Internal Transfer terkait.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($kasBank) {
                // Hapus jurnal saldo awal jika ada
                if ($kasBank->id_jurnal_saldo_awal) {
                    $jurnal = \App\Models\JurnalHeader::find($kasBank->id_jurnal_saldo_awal);
                    if ($jurnal) {
                        \App\Models\JurnalDetail::where('id_jurnal', $jurnal->id)->delete();
                        $jurnal->delete();
                    }
                }
                $kasBank->delete();
            });
            return redirect()->back()->with('success', 'Kas/Bank berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus. Data sedang digunakan.');
        }
    }

    private function handleJournal(KasBank $kasBank, $saldoAwal)
    {
        // 1. Jika Saldo Awal 0, tapi ada jurnal -> Hapus Jurnal
        if ($saldoAwal == 0) {
            if ($kasBank->id_jurnal_saldo_awal) {
                JurnalHeader::destroy($kasBank->id_jurnal_saldo_awal);
                $kasBank->id_jurnal_saldo_awal = null;
                $kasBank->save();
            }
            return;
        }

        // 2. Cari Akun Modal Awal (Equity)
        $akunModalId = \App\Models\Setting::where('key', 'account_equity_opening')->value('value');
        $akunModal = null;
        
        if ($akunModalId) {
            $akunModal = AkunGl::find($akunModalId);
        }

        if (!$akunModal) {
            // Fallback jika belum ada setting atau akun tidak ditemukan (seharusnya ada dari seeder)
            $akunModal = AkunGl::where('kode_akun', '3-0000')->first();
            
            if (!$akunModal) {
                $akunModal = AkunGl::firstOrCreate(
                    ['kode_akun' => '3-0000'],
                    ['nama_akun' => 'Modal Awal (Opening Balance Equity)', 'tipe_akun' => 'Modal']
                );
            }
        }

        // 3. Update atau Create Jurnal
        $jurnal = null;
        if ($kasBank->id_jurnal_saldo_awal) {
            $jurnal = JurnalHeader::find($kasBank->id_jurnal_saldo_awal);
        }

        if (!$jurnal) {
            $jurnal = JurnalHeader::create([
                'nomor_jurnal' => DocumentNumberService::jurnalAdjustment(),
                'tgl_jurnal' => Carbon::now()->startOfYear(),
                'deskripsi_jurnal' => 'Saldo Awal ' . $kasBank->nama_bank,
                'tipe_transaksi' => 'Saldo Awal',
                'status' => 'Posted',
                'sumber_modul' => 'GL'
            ]);
            $kasBank->id_jurnal_saldo_awal = $jurnal->id;
            $kasBank->save();
        } else {
            // Update deskripsi jika nama bank berubah
            $jurnal->update([
                'deskripsi_jurnal' => 'Saldo Awal ' . $kasBank->nama_bank
            ]);
            // Hapus detail lama untuk dibuat ulang
            JurnalDetail::where('id_jurnal', $jurnal->id)->delete();
        }

        // 4. Buat Detail Jurnal
        // Debit: Bank
        JurnalDetail::create([
            'id_jurnal' => $jurnal->id,
            'id_akun' => $kasBank->id_akun_gl,
            'debit' => $saldoAwal,
            'kredit' => 0,
            'keterangan_baris' => 'Saldo Awal'
        ]);

        // Kredit: Modal
        JurnalDetail::create([
            'id_jurnal' => $jurnal->id,
            'id_akun' => $akunModal->id,
            'debit' => 0,
            'kredit' => $saldoAwal,
            'keterangan_baris' => 'Penyeimbang Saldo Awal'
        ]);
    }
}
