<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\AkunGl;
use App\Models\BudgetMaster;
use App\Models\InvoiceDetail;
use App\Models\InvoiceHeader;
use App\Models\JurnalHeader;
use App\Models\PeriodeAnggaran;
use App\Models\PosAnggaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * InvoiceService
 *
 * Mengenkapsulasi seluruh business logic untuk Invoice:
 * penomoran, kalkulasi pajak, posting GL, budget realisasi, dan reversal.
 */
class InvoiceService
{
    protected RevenueBudgetService $revenueBudget;

    public function __construct(RevenueBudgetService $revenueBudget)
    {
        $this->revenueBudget = $revenueBudget;
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function createInvoice(array $data): InvoiceHeader
    {
        return DB::transaction(function () use ($data) {
            // 1. Kalkulasi subtotal
            $subtotal = $this->calculateSubtotal($data['items']);

            // 2. Kalkulasi pajak
            [$ppnRate, $ppnAmount, $pphRate, $pphAmount] = $this->calculateTaxes(
                $subtotal,
                $data['id_tax_ppn'] ?? null,
                $data['id_tax_pph'] ?? null
            );

            $totalTagihan = $subtotal + $ppnAmount;

            // 3. Nomor Invoice (atomic, no race condition)
            $nomorInvoice = DocumentNumberService::invoice();

            // 4. Buat Header — status DRAFT (GL belum posting)
            $invoice = InvoiceHeader::create([
                'nomor_invoice'   => $nomorInvoice,
                'id_departemen'   => $data['id_departemen'],
                'id_pelanggan'    => $data['id_pelanggan'],
                'tgl_invoice'     => $data['tgl_invoice'],
                'tgl_jatuh_tempo' => $data['tgl_jatuh_tempo'],
                'status'          => InvoiceStatus::Draft, // ← DRAFT, bukan Unpaid
                'subtotal'        => $subtotal,
                'id_tax_ppn'      => $data['id_tax_ppn'] ?? null,
                'ppn_rate'        => $ppnRate,
                'ppn_amount'      => $ppnAmount,
                'id_tax_pph'      => $data['id_tax_pph'] ?? null,
                'pph_rate'        => $pphRate,
                'pph_amount'      => $pphAmount,
                'total_tagihan'   => $totalTagihan,
                'sisa_tagihan'    => $totalTagihan,
                'catatan'         => $data['catatan'] ?? null,
                'created_by'      => Auth::id(),
            ]);

            // 5. Buat Detail (tanpa GL posting)
            $this->createDetails($invoice, $data['items']);

            // 6. Initiate Approval Workflow
            // GL hanya akan posting setelah final approved (lihat postGLAfterApproval)
            app(\App\Services\ApprovalService::class)->initApproval($invoice);

            return $invoice;
        });
    }

    // =========================================================================
    // GL POSTING AFTER FINAL APPROVAL (dipanggil oleh ApprovalService)
    // =========================================================================

    /**
     * Post GL dan update budget realisasi setelah invoice mendapat final approval.
     * Dipanggil secara otomatis oleh ApprovalService::finalizeApproval().
     */
    public function postGLAfterApproval(InvoiceHeader $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $invoice->load('detail');

            // Build GL entries dari detail yang sudah tersimpan
            $glDetails = [];

            foreach ($invoice->detail as $item) {
                $glDetails[] = [
                    'id_akun'    => $item->id_akun_pendapatan,
                    'debit'      => 0,
                    'kredit'     => $item->total_harga,
                    'keterangan' => $item->deskripsi_item,
                ];
            }

            // GL entries untuk pajak & piutang
            $glDetails = array_merge($glDetails, $this->buildTaxGLEntries(
                $invoice,
                $invoice->ppn_amount,
                $invoice->pph_amount,
                $invoice->id_tax_ppn,
                $invoice->id_tax_pph
            ));

            $glDetails[] = $this->buildAREntry($invoice->nomor_invoice, $invoice->total_tagihan - $invoice->pph_amount);

            // Posting GL
            GLService::createJournal(
                $invoice->tgl_invoice,
                'Invoice Penjualan ' . $invoice->nomor_invoice,
                $glDetails,
                'AR',
                $invoice->id,
                'Invoice'
            );

            // Update budget realisasi pendapatan
            $this->revenueBudget->updateRealization($invoice);

            // Update status → Unpaid (siap ditagihkan)
            $invoice->update(['status' => InvoiceStatus::Unpaid]);
        });
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function updateInvoice(InvoiceHeader $invoice, array $data): InvoiceHeader
    {
        return DB::transaction(function () use ($invoice, $data) {
            // 1. Reversal budget lama
            $invoice->load('detail');
            $this->reverseRevenueBudget($invoice);

            // 2. Hapus jurnal lama
            JurnalHeader::where('sumber_modul', 'AR')
                ->where('id_referensi_sumber', $invoice->id)
                ->where('tipe_transaksi', 'Invoice')
                ->delete();

            // 3. Hapus detail lama
            $invoice->detail()->delete();

            // 4. Kalkulasi nilai baru
            $subtotal = $this->calculateSubtotal($data['items']);
            [$ppnRate, $ppnAmount, $pphRate, $pphAmount] = $this->calculateTaxes(
                $subtotal,
                $data['id_tax_ppn'] ?? null,
                $data['id_tax_pph'] ?? null
            );
            $totalTagihan = $subtotal + $ppnAmount;

            // 5. Update header
            $invoice->update([
                'id_departemen'  => $data['id_departemen'],
                'id_pelanggan'   => $data['id_pelanggan'],
                'tgl_invoice'    => $data['tgl_invoice'],
                'tgl_jatuh_tempo'=> $data['tgl_jatuh_tempo'],
                'subtotal'       => $subtotal,
                'id_tax_ppn'     => $data['id_tax_ppn'] ?? null,
                'ppn_rate'       => $ppnRate,
                'ppn_amount'     => $ppnAmount,
                'id_tax_pph'     => $data['id_tax_pph'] ?? null,
                'pph_rate'       => $pphRate,
                'pph_amount'     => $pphAmount,
                'total_tagihan'  => $totalTagihan,
                'sisa_tagihan'   => $totalTagihan,
                'catatan'        => $data['catatan'] ?? null,
            ]);

            // 6. Buat detail baru + GL
            $glDetails = $this->createDetails($invoice, $data['items']);
            $glDetails = array_merge($glDetails, $this->buildTaxGLEntries(
                $invoice, $ppnAmount, $pphAmount,
                $data['id_tax_ppn'] ?? null, $data['id_tax_pph'] ?? null
            ));
            $glDetails[] = $this->buildAREntry($invoice->nomor_invoice, $totalTagihan - $pphAmount);

            GLService::createJournal(
                $data['tgl_invoice'],
                'Invoice Penjualan ' . $invoice->nomor_invoice,
                $glDetails,
                'AR',
                $invoice->id,
                'Invoice'
            );

            // 7. Update budget realisasi baru
            $invoice->load('detail');
            $this->revenueBudget->updateRealization($invoice);

            return $invoice->fresh();
        });
    }

    // =========================================================================
    // CANCEL (Void)
    // =========================================================================

    public function cancelInvoice(InvoiceHeader $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            // 1. Reverse budget realisasi
            $invoice->load('detail');
            $this->reverseRevenueBudget($invoice);

            // 2. Reversal journal (tidak hapus — audit trail)
            $originalJournal = JurnalHeader::where('sumber_modul', 'AR')
                ->where('id_referensi_sumber', $invoice->id)
                ->where('tipe_transaksi', 'Invoice')
                ->first();

            if ($originalJournal) {
                $originalJournal->load('detail');
                $reversalDetails = $originalJournal->detail->map(fn($d) => [
                    'id_akun'    => $d->id_akun,
                    'debit'      => $d->kredit,
                    'kredit'     => $d->debit,
                    'keterangan' => 'Reversal (Void) Invoice ' . $invoice->nomor_invoice,
                ])->toArray();

                GLService::createJournal(
                    now()->toDateString(),
                    'Void Invoice ' . $invoice->nomor_invoice,
                    $reversalDetails,
                    'AR',
                    $invoice->id,
                    'Void'
                );
            }

            // 3. Update status
            $invoice->update([
                'status'       => InvoiceStatus::Cancelled,
                'sisa_tagihan' => 0,
            ]);
        });
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroyInvoice(InvoiceHeader $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $invoice->load('detail');
            $this->reverseRevenueBudget($invoice);

            JurnalHeader::where('sumber_modul', 'AR')
                ->where('id_referensi_sumber', $invoice->id)
                ->delete();

            $invoice->detail()->delete();
            $invoice->delete();
        });
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Hitung subtotal dari items array.
     */
    private function calculateSubtotal(array $items): float
    {
        return collect($items)->sum(fn($i) => $i['kuantitas'] * $i['harga_satuan']);
    }

    /**
     * Hitung PPN dan PPh. Return [ppnRate, ppnAmount, pphRate, pphAmount].
     * ✅ Batch-load kedua tax type sekaligus — hanya 1 query jika keduanya ada.
     */
    private function calculateTaxes(float $subtotal, ?int $ppnId, ?int $pphId): array
    {
        $ppnRate = $ppnAmount = $pphRate = $pphAmount = 0;

        $ids = array_filter([$ppnId, $pphId]);
        if (!empty($ids)) {
            $taxes = \App\Models\TaxType::whereIn('id', $ids)->get()->keyBy('id');

            if ($ppnId && $tax = $taxes->get($ppnId)) {
                $ppnRate   = $tax->rate;
                $ppnAmount = $subtotal * ($ppnRate / 100);
            }
            if ($pphId && $tax = $taxes->get($pphId)) {
                $pphRate   = $tax->rate;
                $pphAmount = $subtotal * ($pphRate / 100);
            }
        }

        return [$ppnRate, $ppnAmount, $pphRate, $pphAmount];
    }

    /**
     * Buat InvoiceDetail records + GL debit entries untuk tiap item.
     */
    private function createDetails(InvoiceHeader $invoice, array $items): array
    {
        $glDetails = [];
        foreach ($items as $item) {
            $totalLine = $item['kuantitas'] * $item['harga_satuan'];

            InvoiceDetail::create([
                'id_invoice'        => $invoice->id,
                'deskripsi_item'    => $item['deskripsi_item'],
                'kuantitas'         => $item['kuantitas'],
                'harga_satuan'      => $item['harga_satuan'],
                'total_harga'       => $totalLine,
                'id_akun_pendapatan'=> $item['id_akun_pendapatan'],
            ]);

            $glDetails[] = [
                'id_akun'    => $item['id_akun_pendapatan'],
                'debit'      => 0,
                'kredit'     => $totalLine,
                'keterangan' => 'Pendapatan Invoice ' . $invoice->nomor_invoice . ' - ' . $item['deskripsi_item'],
            ];
        }
        return $glDetails;
    }

    /**
     * Bangun GL entries untuk PPN & PPh.
     * ✅ Memakai taxes yang sudah di-load via calculateTaxes jika dipanggil setelahnya.
     */
    private function buildTaxGLEntries(
        InvoiceHeader $invoice,
        float $ppnAmount,
        float $pphAmount,
        ?int $ppnId,
        ?int $pphId
    ): array {
        $entries = [];
        $ids = array_filter([$ppnId, $pphId]);

        $taxes = !empty($ids)
            ? \App\Models\TaxType::whereIn('id', $ids)->get()->keyBy('id')
            : collect();

        if ($ppnAmount > 0 && $ppnId && $tax = $taxes->get($ppnId)) {
            $entries[] = [
                'id_akun'    => $tax->id_akun_gl,
                'debit'      => 0,
                'kredit'     => $ppnAmount,
                'keterangan' => 'PPN Keluaran Invoice ' . $invoice->nomor_invoice,
            ];
        }
        if ($pphAmount > 0 && $pphId && $tax = $taxes->get($pphId)) {
            $entries[] = [
                'id_akun'    => $tax->id_akun_gl,
                'debit'      => $pphAmount,
                'kredit'     => 0,
                'keterangan' => 'Piutang PPh (Prepaid) Invoice ' . $invoice->nomor_invoice,
            ];
        }
        return $entries;
    }

    /**
     * Bangun GL entry Piutang Usaha (AR Debit).
     */
    private function buildAREntry(string $nomorInvoice, float $amount): array
    {
        $arAccountId = config('accounting.accounts.ar');
        if (!$arAccountId) {
            $arAccount   = AkunGl::where('nama_akun', 'like', '%Piutang Usaha%')->first()
                          ?? AkunGl::where('tipe_akun', 'Piutang')->first();
            $arAccountId = $arAccount?->id;
        }
        if (!$arAccountId) {
            throw new \Exception('Akun Piutang Usaha tidak ditemukan. Cek config accounting.accounts.ar atau data GL.');
        }

        return [
            'id_akun'    => $arAccountId,
            'debit'      => $amount,
            'kredit'     => 0,
            'keterangan' => 'Piutang Invoice ' . $nomorInvoice,
        ];
    }

    /**
     * Reverse budget realisasi — dipakai saat update/cancel/delete.
     * ✅ Batch-load PosAnggaran dan PeriodeAnggaran — tidak ada N+1.
     */
    public function reverseRevenueBudget(InvoiceHeader $invoice): void
    {
        if ($invoice->detail->isEmpty()) return;

        // 1. Temukan periode anggaran yang relevan (1 query)
        $periodeId = PeriodeAnggaran::whereDate('tanggal_mulai', '<=', $invoice->tgl_invoice)
            ->whereDate('tanggal_selesai', '>=', $invoice->tgl_invoice)
            ->value('id');

        if (!$periodeId) return;

        // 2. Load semua PosAnggaran relevan sekaligus (1 query)
        $akunIds = $invoice->detail->pluck('id_akun_pendapatan');
        $posAnggaranMap = PosAnggaran::whereIn('id_akun_gl', $akunIds)
            ->whereHas('programKerja', fn($q) => $q->where('id_departemen', $invoice->id_departemen))
            ->get()
            ->keyBy('id_akun_gl');

        // 3. Aggregate decrement per PosAnggaran (1 query per pos, max = jumlah akun berbeda)
        $decrements = [];
        foreach ($invoice->detail as $item) {
            $pos = $posAnggaranMap->get($item->id_akun_pendapatan);
            if (!$pos) continue;

            $decrements[$pos->id] = ($decrements[$pos->id] ?? 0) + $item->total_harga;
        }

        foreach ($decrements as $posId => $amount) {
            BudgetMaster::where('id_periode_anggaran', $periodeId)
                ->where('id_pos_anggaran', $posId)
                ->decrement('anggaran_realisasi_ytd', $amount);
        }
    }
}
