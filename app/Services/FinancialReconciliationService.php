<?php

namespace App\Services;

use App\Models\ReconciliationLog;
use App\Models\PengajuanHeader;
use Illuminate\Support\Facades\DB;

class FinancialReconciliationService
{
    /**
     * Reconcile Pengajuan expenditures against Payment/Disbursement records for a given period.
     */
    public function reconcilePengajuan(string $period, ?int $reconciledBy = null): ReconciliationLog
    {
        // Period format: YYYY-MM
        $totalApproved = 0.0;
        $totalPaid = 0.0;

        if (\Illuminate\Support\Facades\Schema::hasTable('tbl_pengajuan_header')) {
            $totalApproved = (float) DB::table('tbl_pengajuan_header')
                ->where('status_global', 'Approved')
                ->where('tgl_pengajuan', 'like', "{$period}%")
                ->sum('total_nominal');

            $totalPaid = (float) DB::table('tbl_pengajuan_header')
                ->where('status_global', 'Paid')
                ->where('tgl_pengajuan', 'like', "{$period}%")
                ->sum('total_nominal');
        }

        $discrepancy = round($totalApproved - $totalPaid, 2);
        $status = abs($discrepancy) < 0.01 ? 'Balanced' : 'Discrepancy_Found';

        return ReconciliationLog::create([
            'period' => $period,
            'module' => 'Pengajuan',
            'total_expected' => $totalApproved,
            'total_actual' => $totalPaid,
            'discrepancy' => $discrepancy,
            'status' => $status,
            'details' => [
                'total_approved_amount' => $totalApproved,
                'total_paid_amount' => $totalPaid,
                'reconciled_at' => now()->toIso8601String(),
            ],
            'reconciled_by' => $reconciledBy,
        ]);
    }

    /**
     * Reconcile Payroll against General Ledger postings.
     */
    public function reconcilePayroll(string $period, ?int $reconciledBy = null): ReconciliationLog
    {
        $payrollTotal = 0.0;
        $ledgerTotal = 0.0;

        if (\Illuminate\Support\Facades\Schema::hasTable('tbl_payroll')) {
            $payrollTotal = (float) DB::table('tbl_payroll')
                ->where('bulan_periode', $period)
                ->whereIn('status', ['Approved', 'Paid'])
                ->sum('total_gaji_bersih');
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('tbl_jurnal_detail')) {
            $ledgerTotal = (float) DB::table('tbl_jurnal_detail')
                ->join('tbl_jurnal_header', 'tbl_jurnal_detail.id_jurnal_header', '=', 'tbl_jurnal_header.id')
                ->where('tbl_jurnal_header.tgl_jurnal', 'like', "{$period}%")
                ->where('tbl_jurnal_header.deskripsi', 'like', '%Gaji%')
                ->sum('tbl_jurnal_detail.debit');
        }

        $discrepancy = round($payrollTotal - $ledgerTotal, 2);
        $status = abs($discrepancy) < 0.01 ? 'Balanced' : 'Discrepancy_Found';

        return ReconciliationLog::create([
            'period' => $period,
            'module' => 'Payroll',
            'total_expected' => $payrollTotal,
            'total_actual' => $ledgerTotal,
            'discrepancy' => $discrepancy,
            'status' => $status,
            'details' => [
                'payroll_total_net' => $payrollTotal,
                'ledger_recorded_total' => $ledgerTotal,
                'reconciled_at' => now()->toIso8601String(),
            ],
            'reconciled_by' => $reconciledBy,
        ]);
    }
}
