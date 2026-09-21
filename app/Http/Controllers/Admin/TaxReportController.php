<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunGl;
use App\Models\JurnalDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class TaxReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->start_date ? \Carbon\Carbon::parse($request->start_date) : \Carbon\Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date) : \Carbon\Carbon::now()->endOfMonth();

        // Helper to get balance
        $getBalance = function ($accountId, $type = 'credit') use ($startDate, $endDate) {
            if (!$accountId) return 0;
            
            $query = JurnalDetail::where('id_akun', $accountId)
                ->whereHas('header', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tgl_jurnal', [$startDate, $endDate]);
                });

            if ($type === 'credit') {
                return $query->selectRaw('sum(kredit) - sum(debit) as balance')->value('balance') ?? 0;
            } else {
                return $query->selectRaw('sum(debit) - sum(kredit) as balance')->value('balance') ?? 0;
            }
        };

        // 1. PPN (VAT) Summary
        
        // A. Output VAT (Hutang PPN) - From Sales
        $ppnSalesTypes = \App\Models\TaxType::where('tipe', 'PPN')
            ->where('transaction_type', 'sales')
            ->whereNotNull('id_akun_gl')
            ->with('akunGl')
            ->get();

        $ppnPayableDetails = [];
        $totalPpnPayable = 0;
        $processedPpnPayableIds = [];

        foreach ($ppnSalesTypes as $tax) {
            if (in_array($tax->id_akun_gl, $processedPpnPayableIds)) continue;
            
            $balance = $getBalance($tax->id_akun_gl, 'credit'); // Credit - Debit (Liability)
            
            // Include if balance exists OR if it's a configured tax account (even if 0)
            if ($balance != 0 || true) { 
                $ppnPayableDetails[] = [
                    'id' => $tax->id_akun_gl,
                    'nama_akun' => $tax->akunGl->nama_akun,
                    'balance' => $balance
                ];
                $totalPpnPayable += $balance;
                $processedPpnPayableIds[] = $tax->id_akun_gl;
            }
        }

        // Fallback for Payable if no sales tax types found but config exists
        $defaultPpnOut = config('accounting.accounts.vat_out');
        if ($defaultPpnOut && !in_array($defaultPpnOut, $processedPpnPayableIds)) {
            $balance = $getBalance($defaultPpnOut, 'credit');
            $account = AkunGl::find($defaultPpnOut);
            if ($account) {
                $ppnPayableDetails[] = [
                    'id' => $account->id,
                    'nama_akun' => $account->nama_akun,
                    'balance' => $balance
                ];
                $totalPpnPayable += $balance;
                $processedPpnPayableIds[] = $defaultPpnOut;
            }
        }

        // B. Input VAT (Piutang PPN) - From Purchases
        $ppnPurchaseTypes = \App\Models\TaxType::where('tipe', 'PPN')
            ->where('transaction_type', 'purchase')
            ->whereNotNull('id_akun_gl')
            ->with('akunGl')
            ->get();
            
        $ppnReceivableDetails = [];
        $totalPpnReceivable = 0;
        $processedPpnReceivableIds = [];

        foreach ($ppnPurchaseTypes as $tax) {
             if (in_array($tax->id_akun_gl, $processedPpnReceivableIds)) continue;
             
             $balance = $getBalance($tax->id_akun_gl, 'debit'); // Debit - Credit (Asset)
             
             if ($balance != 0 || true) {
                 $ppnReceivableDetails[] = [
                     'id' => $tax->id_akun_gl,
                     'nama_akun' => $tax->akunGl->nama_akun,
                     'balance' => $balance
                 ];
                 $totalPpnReceivable += $balance;
                 $processedPpnReceivableIds[] = $tax->id_akun_gl;
            }
        }

        // Fallback for Receivable
        $defaultPpnIn = config('accounting.accounts.vat_in');
        if ($defaultPpnIn && !in_array($defaultPpnIn, $processedPpnReceivableIds)) {
            $balance = $getBalance($defaultPpnIn, 'debit');
            $account = AkunGl::find($defaultPpnIn);
            if ($account) {
                $ppnReceivableDetails[] = [
                    'id' => $account->id,
                    'nama_akun' => $account->nama_akun,
                    'balance' => $balance
                ];
                $totalPpnReceivable += $balance;
                $processedPpnReceivableIds[] = $defaultPpnIn;
            }
        }
        
        // If still empty, try legacy name search
        if (empty($processedPpnReceivableIds)) {
             $acc = AkunGl::where('nama_akun', 'like', '%Piutang PPN%')->orWhere('nama_akun', 'like', '%PPN Masukan%')->first();
             if ($acc) {
                 $balance = $getBalance($acc->id, 'debit');
                 $ppnReceivableDetails[] = [
                     'id' => $acc->id,
                     'nama_akun' => $acc->nama_akun,
                     'balance' => $balance
                 ];
                 $totalPpnReceivable += $balance;
             }
        }

        // 2. PPh (Withholding Tax) Summary
        
        // A. Payable PPh (Kewajiban) - From Purchases/Vendor
        $pphPayableTypes = \App\Models\TaxType::where('tipe', 'PPh')
            ->where('transaction_type', 'purchase') // Explicitly filter purchase
            ->whereNotNull('id_akun_gl')
            ->with('akunGl')
            ->get();

        $pphPayableSummary = [];
        $totalPphPayable = 0;
        $processedPayableIds = [];

        foreach ($pphPayableTypes as $taxType) {
            if (in_array($taxType->id_akun_gl, $processedPayableIds)) continue;
            
            $balance = $getBalance($taxType->id_akun_gl, 'credit'); // Credit - Debit
            
            if ($balance > 0) {
                $pphPayableSummary[] = [
                    'id' => $taxType->id_akun_gl,
                    'nama_akun' => $taxType->akunGl->nama_akun,
                    'balance' => $balance
                ];
                $totalPphPayable += $balance;
                $processedPayableIds[] = $taxType->id_akun_gl;
            }
        }

        // Fallback for Payable PPh (Legacy/Config)
        $defaultPphId = config('accounting.accounts.pph_payable');
        if ($defaultPphId && !in_array($defaultPphId, $processedPayableIds)) {
            $balance = $getBalance($defaultPphId, 'credit');
            if ($balance > 0) {
                $account = AkunGl::find($defaultPphId);
                if ($account) {
                    $pphPayableSummary[] = [
                        'id' => $account->id,
                        'nama_akun' => $account->nama_akun,
                        'balance' => $balance
                    ];
                    $totalPphPayable += $balance;
                }
            }
        }

        // B. Prepaid PPh (Kredit Pajak) - From Sales/Customers
        $pphPrepaidTypes = \App\Models\TaxType::where('tipe', 'PPh')
            ->where('transaction_type', 'sales') // Explicitly filter sales
            ->whereNotNull('id_akun_gl')
            ->with('akunGl')
            ->get();

        $pphPrepaidSummary = [];
        $totalPphPrepaid = 0;
        $processedPrepaidIds = [];

        foreach ($pphPrepaidTypes as $taxType) {
            if (in_array($taxType->id_akun_gl, $processedPrepaidIds)) continue;
            
            $balance = $getBalance($taxType->id_akun_gl, 'debit'); // Debit - Credit (Asset)
            
            if ($balance > 0) {
                $pphPrepaidSummary[] = [
                    'id' => $taxType->id_akun_gl,
                    'nama_akun' => $taxType->akunGl->nama_akun,
                    'balance' => $balance
                ];
                $totalPphPrepaid += $balance;
                $processedPrepaidIds[] = $taxType->id_akun_gl;
            }
        }

        return Inertia::render('Admin/Tax/Index', [
            'ppn' => [
                'payable_details' => $ppnPayableDetails,
                'total_payable' => $totalPpnPayable,
                'receivable_details' => $ppnReceivableDetails,
                'total_receivable' => $totalPpnReceivable,
                'net_payable' => $totalPpnPayable - $totalPpnReceivable
            ],
            'pph' => [
                'payable_details' => $pphPayableSummary,
                'total_payable' => $totalPphPayable,
                'prepaid_details' => $pphPrepaidSummary,
                'total_prepaid' => $totalPphPrepaid
            ],
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        ]);
    }

    public function show($id, Request $request)
    {
        $account = AkunGl::findOrFail($id);
        
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');
        
        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Fetch transactions with related Invoice info if available
        $transactions = JurnalDetail::with(['header']) 
            ->where('id_akun', $id)
            ->whereHas('header', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tgl_jurnal', [$startDate, $endDate]);
            })
            ->get();
            
        // Manual mapping for Invoice info (Bukti Potong)
        foreach ($transactions as $trx) {
            $invoice = null;

            // 1. Try via Reference ID (Best Practice)
            if ($trx->header->sumber_modul === 'AR' && $trx->header->id_referensi_sumber) {
                $invoice = \App\Models\InvoiceHeader::find($trx->header->id_referensi_sumber);
            }

            // 2. Fallback via Regex on Description
            if (!$invoice && preg_match('/Invoice (INV-\d+-\d+)/', $trx->keterangan, $matches)) {
                $invNumber = $matches[1];
                $invoice = \App\Models\InvoiceHeader::where('nomor_invoice', $invNumber)->first();
            }

            if ($invoice) {
                $trx->invoice_id = $invoice->id;
                $trx->bukti_potong_path = $invoice->bukti_potong_path;
                $trx->has_bukti_potong = !empty($invoice->bukti_potong_path);
            }
        }

        $totalDebit = $transactions->sum('debit');
        $totalCredit = $transactions->sum('kredit');
        
        $isAsset = in_array($account->tipe_akun, ['Aset', 'Aset Lancar', 'Kas & Bank']);
        $endingBalance = $isAsset ? ($totalDebit - $totalCredit) : ($totalCredit - $totalDebit);

        // Determine if this is a PPh account to show Bukti Potong column
        $isPPh = stripos($account->nama_akun, 'PPh') !== false;

        return Inertia::render('Admin/Tax/Show', [
            'account' => $account,
            'transactions' => $transactions,
            'is_pph' => $isPPh,
            'filters' => [
                'month' => $month,
                'year' => $year
            ],
            'summary' => [
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'ending_balance' => $endingBalance
            ]
        ]);
    }

    public function uploadBuktiPotong(Request $request, $invoiceId)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        $invoice = \App\Models\InvoiceHeader::findOrFail($invoiceId);
        
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('bukti_potong', 'public');
            $invoice->update(['bukti_potong_path' => $path]);
        }

        return redirect()->back()->with('success', 'Bukti Potong berhasil diupload.');
    }
}
