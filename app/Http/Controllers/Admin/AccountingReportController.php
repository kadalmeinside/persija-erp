<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunGl;
use App\Models\JurnalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AccountingReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-t');
        $reportType = $request->type ?? 'neraca_saldo'; // neraca_saldo, laba_rugi, neraca

        $data = [];

        if ($reportType === 'neraca_saldo') {
            $data = $this->getTrialBalance($startDate, $endDate);
        } elseif ($reportType === 'laba_rugi') {
            $data = $this->getProfitLoss($startDate, $endDate);
        } elseif ($reportType === 'neraca') {
            $data = $this->getBalanceSheet($endDate);
        }

        return Inertia::render('Admin/Accounting/Reports/Index', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $reportType
            ],
            'reportData' => $data
        ]);
    }

    private function getTrialBalance($startDate, $endDate)
    {
        // Get all accounts with their movements within the period
        // Also need opening balance (movements before start date)
        
        $accounts = AkunGl::orderBy('kode_akun')->get();
        
        $report = $accounts->map(function ($account) use ($startDate, $endDate) {
            // Opening Balance
            $openingDebit = JurnalDetail::where('id_akun', $account->id)
                ->whereHas('header', function ($q) use ($startDate) {
                    $q->where('tgl_jurnal', '<', $startDate)->where('status', 'Posted');
                })->sum('debit');
                
            $openingCredit = JurnalDetail::where('id_akun', $account->id)
                ->whereHas('header', function ($q) use ($startDate) {
                    $q->where('tgl_jurnal', '<', $startDate)->where('status', 'Posted');
                })->sum('kredit');

            $openingBalance = $openingDebit - $openingCredit; // Normal Debit

            // Current Movements
            $currentDebit = JurnalDetail::where('id_akun', $account->id)
                ->whereHas('header', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tgl_jurnal', [$startDate, $endDate])->where('status', 'Posted');
                })->sum('debit');

            $currentCredit = JurnalDetail::where('id_akun', $account->id)
                ->whereHas('header', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tgl_jurnal', [$startDate, $endDate])->where('status', 'Posted');
                })->sum('kredit');

            // Ending Balance
            $endingBalance = $openingBalance + ($currentDebit - $currentCredit);

            return [
                'kode_akun' => $account->kode_akun,
                'nama_akun' => $account->nama_akun,
                'tipe' => $account->tipe_akun,
                'saldo_awal' => $openingBalance,
                'debit' => $currentDebit,
                'kredit' => $currentCredit,
                'saldo_akhir' => $endingBalance
            ];
        });

        return $report->filter(function ($item) {
            return abs($item['saldo_awal']) > 0 || abs($item['debit']) > 0 || abs($item['kredit']) > 0;
        })->values();
    }

    private function getProfitLoss($startDate, $endDate)
    {
        // Revenue (Pendapatan) - Expenses (Beban)
        // Assuming Account Types: 'Pendapatan', 'Beban'
        
        $revenue = $this->getAccountGroupBalance(['Pendapatan'], $startDate, $endDate);
        $expenses = $this->getAccountGroupBalance(['Beban'], $startDate, $endDate);

        return [
            'pendapatan' => $revenue,
            'total_pendapatan' => $revenue->sum('balance') * -1, // Revenue is Credit normal, so multiply by -1 for display
            'beban' => $expenses,
            'total_beban' => $expenses->sum('balance'),
            'laba_bersih' => ($revenue->sum('balance') * -1) - $expenses->sum('balance')
        ];
    }

    private function getBalanceSheet($endDate)
    {
        // Assets = Liabilities + Equity
        // Equity needs to include Current Year Earnings (Laba Tahun Berjalan)
        
        $assets = $this->getAccountGroupBalance(['Aset', 'Kas & Bank', 'Piutang', 'Persediaan', 'Aset Tetap'], null, $endDate);
        $liabilities = $this->getAccountGroupBalance(['Kewajiban', 'Hutang'], null, $endDate);
        $equity = $this->getAccountGroupBalance(['Ekuitas', 'Modal'], null, $endDate);

        // Calculate Current Year Earnings (Revenue - Expense up to End Date)
        // Note: Ideally this should be calculated from the beginning of the fiscal year.
        // For simplicity, assuming fiscal year starts Jan 1st.
        $fiscalYearStart = date('Y-01-01', strtotime($endDate));
        
        $revenue = $this->getAccountGroupBalance(['Pendapatan'], $fiscalYearStart, $endDate)->sum('balance') * -1;
        $expenses = $this->getAccountGroupBalance(['Beban'], $fiscalYearStart, $endDate)->sum('balance');
        $currentEarnings = $revenue - $expenses;

        return [
            'aset' => $assets,
            'total_aset' => $assets->sum('balance'),
            'kewajiban' => $liabilities,
            'total_kewajiban' => $liabilities->sum('balance') * -1, // Liability is Credit normal
            'ekuitas' => $equity,
            'laba_tahun_berjalan' => $currentEarnings,
            'total_ekuitas' => ($equity->sum('balance') * -1) + $currentEarnings
        ];
    }

    private function getAccountGroupBalance($types, $startDate, $endDate)
    {
        $accounts = AkunGl::whereIn('tipe_akun', $types)->orderBy('kode_akun')->get();

        return $accounts->map(function ($account) use ($startDate, $endDate) {
            $query = JurnalDetail::where('id_akun', $account->id)
                ->whereHas('header', function ($q) use ($endDate) {
                    $q->where('tgl_jurnal', '<=', $endDate)->where('status', 'Posted');
                });

            if ($startDate) {
                $query->whereHas('header', function ($q) use ($startDate) {
                    $q->where('tgl_jurnal', '>=', $startDate);
                });
            }

            $debit = (clone $query)->sum('debit');
            $credit = (clone $query)->sum('kredit');
            $balance = $debit - $credit;

            return [
                'kode_akun' => $account->kode_akun,
                'nama_akun' => $account->nama_akun,
                'balance' => $balance
            ];
        })->filter(function ($item) {
            return abs($item['balance']) > 0;
        })->values();
    }
}
