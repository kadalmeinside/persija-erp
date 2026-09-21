<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunGl;
use App\Models\Departemen;
use App\Models\InvoiceHeader;
use App\Models\KasBank;
use App\Models\Pelanggan;
use App\Models\PenerimaanPembayaran;
use App\Enums\InvoiceStatus;
use App\Services\GLService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService)
    {
    }

    // -------------------------------------------------------------------------
    // Validation rules (reused by store and update)
    // -------------------------------------------------------------------------
    private function validationRules(): array
    {
        return [
            'id_pelanggan'              => 'required|exists:tbl_pelanggan,id',
            'id_departemen'             => 'required|exists:tbl_departemen,id',
            'tgl_invoice'               => 'required|date',
            'tgl_jatuh_tempo'           => 'required|date|after_or_equal:tgl_invoice',
            'catatan'                   => 'nullable|string',
            'items'                     => 'required|array|min:1',
            'items.*.deskripsi_item'    => 'required|string',
            'items.*.kuantitas'         => 'required|integer|min:1',
            'items.*.harga_satuan'      => 'required|numeric|min:0',
            'items.*.id_akun_pendapatan'=> 'required|exists:tbl_akun_gl,id',
            'id_tax_ppn'                => 'nullable|exists:tbl_tax_types,id',
            'id_tax_pph'                => 'nullable|exists:tbl_tax_types,id',
        ];
    }

    // -------------------------------------------------------------------------
    // Master data untuk Create/Edit form
    // -------------------------------------------------------------------------
    private function formMasterData(): array
    {
        return [
            'customers'      => Pelanggan::orderBy('nama_pelanggan')->get(['id', 'nama_pelanggan', 'kode_pelanggan']),
            'revenueAccounts'=> AkunGl::where('tipe_akun', 'Pendapatan')->where('is_active', true)->orderBy('kode_akun')->get(['id', 'kode_akun', 'nama_akun']),
            'taxTypes'       => \App\Models\TaxType::where('is_active', true)->get(['id', 'kode_pajak', 'nama_pajak', 'rate', 'tipe', 'transaction_type']),
            'departments'    => Departemen::orderBy('nama_departemen')->get(['id', 'nama_departemen']),
        ];
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $query = InvoiceHeader::with(['pelanggan:id,nama_pelanggan', 'pembuat:id,name']);

        if ($request->start_date) $query->whereDate('tgl_invoice', '>=', $request->start_date);
        if ($request->end_date)   $query->whereDate('tgl_invoice', '<=', $request->end_date);
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nomor_invoice', 'like', '%' . $request->search . '%')
                  ->orWhereHas('pelanggan', fn($sq) => $sq->where('nama_pelanggan', 'like', '%' . $request->search . '%'));
            });
        }
        if ($request->status) $query->where('status', $request->status);

        // Summary stats (menggunakan clone query agar filter tetap berlaku)
        $totalRevenue = (clone $query)->sum('total_tagihan');
        $totalUnpaid  = (clone $query)->where('status', InvoiceStatus::Unpaid)->sum('sisa_tagihan');
        $totalOverdue = (clone $query)->where('status', InvoiceStatus::Unpaid)
                            ->whereDate('tgl_jatuh_tempo', '<', now())
                            ->sum('sisa_tagihan');

        $invoices = $query->orderBy('tgl_invoice', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Revenue/Invoice/Index', [
            'invoices' => $invoices,
            'filters'  => $request->only(['search', 'status', 'start_date', 'end_date']),
            'summary'  => [
                'total_revenue' => $totalRevenue,
                'total_unpaid'  => $totalUnpaid,
                'total_overdue' => $totalOverdue,
            ],
        ]);
    }

    // =========================================================================
    // CREATE / STORE
    // =========================================================================

    public function create()
    {
        return Inertia::render('Admin/Revenue/Invoice/Create', $this->formMasterData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules());

        try {
            $this->invoiceService->createInvoice($validated);
            return redirect()->route('admin.invoices.index')
                ->with('success', 'Invoice berhasil dibuat dan menunggu persetujuan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat invoice: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show(InvoiceHeader $invoice)
    {
        $invoice->load([
            'pelanggan',
            'detail.akunPendapatan',
            'pembayaran.kasBank',
            'pembuat',
            'approvedBy:id,name',
            'approvalProcess' => fn($q) => $q->orderBy('level_order')
                ->with(['targetKaryawan:id,nama_lengkap']),
        ]);
        $kasBanks = KasBank::where('is_active', true)->get(['id', 'nama_bank', 'nomor_rekening']);

        return Inertia::render('Admin/Revenue/Invoice/Show', [
            'invoice'  => $invoice,
            'kasBanks' => $kasBanks,
        ]);
    }

    // =========================================================================
    // EDIT / UPDATE
    // =========================================================================

    public function edit(InvoiceHeader $invoice)
    {
        // Hanya invoice Draft yang bisa diedit
        if ($invoice->status !== \App\Enums\InvoiceStatus::Draft) {
            return redirect()->back()
                ->with('error', 'Hanya invoice Draft yang dapat diedit. Invoice yang sudah disetujui harus di-void terlebih dahulu.');
        }

        $invoice->load('detail');

        return Inertia::render('Admin/Revenue/Invoice/Edit', array_merge(
            ['invoice' => $invoice],
            $this->formMasterData()
        ));
    }

    public function update(Request $request, InvoiceHeader $invoice)
    {
        if ($invoice->status !== \App\Enums\InvoiceStatus::Draft) {
            return redirect()->back()
                ->with('error', 'Hanya invoice Draft yang dapat diedit.');
        }

        $validated = $request->validate($this->validationRules());

        try {
            $this->invoiceService->updateInvoice($invoice, $validated);
            return redirect()->route('admin.invoices.index')->with('success', 'Invoice berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal update invoice: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy(InvoiceHeader $invoice)
    {
        // Hanya Draft yang boleh dihapus (GL belum posting, approval belum final)
        if ($invoice->status !== \App\Enums\InvoiceStatus::Draft) {
            return redirect()->back()
                ->with('error', 'Hanya invoice Draft yang dapat dihapus. Gunakan Void untuk invoice yang sudah disetujui.');
        }

        try {
            $this->invoiceService->destroyInvoice($invoice);
            return redirect()->route('admin.invoices.index')->with('success', 'Invoice berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal hapus invoice: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // CANCEL (Void)
    // =========================================================================

    public function cancel(InvoiceHeader $invoice)
    {
        // Draft di-cancel lewat delete; yang bisa di-void adalah Unpaid/Partial
        $cancelable = [\App\Enums\InvoiceStatus::Unpaid, \App\Enums\InvoiceStatus::Partial];
        if (!in_array($invoice->status, $cancelable)) {
            return redirect()->back()
                ->with('error', 'Hanya invoice Unpaid atau Partial yang dapat dibatalkan.');
        }

        try {
            $this->invoiceService->cancelInvoice($invoice);
            return redirect()->route('admin.invoices.index')->with('success', 'Invoice berhasil dibatalkan (Void).');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membatalkan invoice: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // APPROVE (dipanggil oleh Finance/Direktur sebagai approver)
    // =========================================================================

    public function approve(Request $request, InvoiceHeader $invoice)
    {
        try {
            $karyawan = Auth::user()->karyawan;
            if (!$karyawan) {
                return back()->with('error', 'Data karyawan Anda tidak ditemukan.');
            }

            $action  = $request->input('action', 'approve'); // approve | reject | revision
            $catatan = $request->input('catatan');

            $approvalService = app(\App\Services\ApprovalService::class);

            match ($action) {
                'approve'  => $approvalService->approve($invoice, $karyawan->id, $catatan),
                'reject'   => $approvalService->reject($invoice, $karyawan->id, $catatan),
                'revision' => $approvalService->revision($invoice, $karyawan->id, $catatan),
                default    => throw new \Exception('Aksi tidak valid.'),
            };

            $messages = [
                'approve'  => 'Invoice berhasil disetujui.',
                'reject'   => 'Invoice berhasil ditolak.',
                'revision' => 'Invoice dikembalikan untuk perbaikan.',
            ];

            return back()->with('success', $messages[$action]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // =========================================================================
    // PAYMENT
    // =========================================================================

    public function storePayment(Request $request, InvoiceHeader $invoice)
    {
        $request->validate([
            'tgl_bayar'    => 'required|date',
            'nominal_bayar'=> 'required|numeric|min:1|max:' . $invoice->sisa_tagihan,
            'id_kas_bank'  => 'required|exists:tbl_kas_bank,id',
            'bukti_bayar'  => 'nullable|file|mimes:jpg,png,pdf|max:2048',
            'catatan'      => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request, $invoice) {
                // 1. Upload bukti
                $path = $request->hasFile('bukti_bayar')
                    ? $request->file('bukti_bayar')->store('receipts', 'public')
                    : null;

                // 2. Catat penerimaan
                PenerimaanPembayaran::create([
                    'id_invoice'    => $invoice->id,
                    'tgl_bayar'     => $request->tgl_bayar,
                    'nominal_bayar' => $request->nominal_bayar,
                    'id_kas_bank'   => $request->id_kas_bank,
                    'bukti_bayar_path' => $path,
                    'catatan'       => $request->catatan,
                    'created_by'    => Auth::id(),
                ]);

                // 3. Update sisa tagihan & status
                $invoice->sisa_tagihan -= $request->nominal_bayar;
                $invoice->status = $invoice->sisa_tagihan <= 0
                    ? InvoiceStatus::Paid
                    : InvoiceStatus::Partial;
                if ($invoice->sisa_tagihan < 0) $invoice->sisa_tagihan = 0;
                $invoice->save();

                // 4. GL: Debit Bank, Credit Piutang
                $kasBank = KasBank::findOrFail($request->id_kas_bank);
                if (!$kasBank->id_akun_gl) throw new \Exception('Kas/Bank tidak terhubung ke Akun GL.');

                $arAccountId = config('accounting.accounts.ar')
                    ?? AkunGl::where('nama_akun', 'like', '%Piutang Usaha%')->value('id')
                    ?? AkunGl::where('tipe_akun', 'Piutang')->value('id');

                if (!$arAccountId) throw new \Exception('Akun Piutang Usaha tidak ditemukan.');

                GLService::createJournal(
                    $request->tgl_bayar,
                    'Penerimaan Pembayaran ' . $invoice->nomor_invoice,
                    [
                        ['id_akun' => $kasBank->id_akun_gl, 'debit' => $request->nominal_bayar, 'kredit' => 0, 'keterangan' => 'Penerimaan Invoice ' . $invoice->nomor_invoice],
                        ['id_akun' => $arAccountId,          'debit' => 0, 'kredit' => $request->nominal_bayar, 'keterangan' => 'Pelunasan Piutang Invoice ' . $invoice->nomor_invoice],
                    ],
                    'AR', $invoice->id, 'Receipt'
                );
            });

            return back()->with('success', 'Pembayaran berhasil dicatat.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // PRINT
    // =========================================================================

    public function print(InvoiceHeader $invoice)
    {
        $invoice->load([
            'pelanggan',
            'detail.akunPendapatan',
            'approvalProcess.actionKaryawan',
            'approvalProcess.targetKaryawan'
        ]);

        foreach ($invoice->approvalProcess as $approval) {
            if ($approval->status === 'Approved' && $approval->uuid) {
                $url = route('public.verify', ['uuid' => $approval->uuid]);
                $approval->qr_code = 'data:image/svg+xml;base64,' . base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(60)->generate($url));
            }
        }

        $settings = \App\Models\Setting::all()->pluck('value', 'key');

        return Inertia::render('Admin/Revenue/Invoice/Print', [
            'invoice'   => $invoice,
            'timestamp' => now()->translatedFormat('d F Y, H:i'),
            'company'   => [
                'name'    => $settings['company_name'] ?? 'Persija ERP',
                'address' => $settings['company_address'] ?? 'Jl. Rasuna Said No. 1, Jakarta Selatan',
                'logo'    => isset($settings['company_logo'])
                    ? \Illuminate\Support\Facades\Storage::url($settings['company_logo'])
                    : null,
            ],
        ]);
    }
}
