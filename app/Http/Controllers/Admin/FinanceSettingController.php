<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\AkunGl;
use App\Models\ProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class FinanceSettingController extends Controller
{

    public function index()
    {

        $settings = Setting::whereIn('key', [
            'account_receivable_employee',
            'account_equity_opening',
            'account_expense_rounding',
            'account_equity_retained',
            'default_program_loans',
            'reimburse_tolerance_limit'
        ])->pluck('value', 'key');

        // Get Asset Accounts (Assets + Receivables)
        $assetAccounts = AkunGl::where('tipe_akun', 'Aset')
            ->orWhere('tipe_akun', 'Piutang')
            ->orWhere('kode_akun', 'like', '1-%')
            ->orderBy('kode_akun')
            ->get(['id', 'kode_akun', 'nama_akun']);

        // Get Equity Accounts (Modal)
        $equityAccounts = AkunGl::where('tipe_akun', 'Modal')
            ->orWhere('tipe_akun', 'Ekuitas')
            ->orWhere('kode_akun', 'like', '3-%')
            ->orderBy('kode_akun')
            ->get(['id', 'kode_akun', 'nama_akun']);

        // Get Expense/Other Accounts (Beban, Biaya, Lain-lain)
        $expenseAccounts = AkunGl::whereIn('tipe_akun', ['Beban', 'Biaya', 'Pendapatan Lain', 'Beban Lain'])
            ->orWhere('kode_akun', 'like', '6-%')
            ->orWhere('kode_akun', 'like', '7-%')
            ->orWhere('kode_akun', 'like', '8-%')
            ->orWhere('kode_akun', 'like', '9-%')
            ->orderBy('kode_akun')
            ->get(['id', 'kode_akun', 'nama_akun']);

        // Get Programs
        $programs = ProgramKerja::orderBy('nama_program')->get(['id', 'nama_program']);

        return Inertia::render('Admin/Finance/Settings/Index', [
            'settings' => $settings,
            'assetAccounts' => $assetAccounts,
            'equityAccounts' => $equityAccounts,
            'expenseAccounts' => $expenseAccounts,
            'programs' => $programs,
            'pageTitle' => 'Pengaturan Keuangan'
        ]);
    }

    public function update(Request $request)
    {

        $validated = $request->validate([
            'account_receivable_employee' => 'nullable|exists:tbl_akun_gl,id',
            'account_equity_opening' => 'nullable|exists:tbl_akun_gl,id',
            'account_expense_rounding' => 'nullable|exists:tbl_akun_gl,id',
            'account_equity_retained' => 'nullable|exists:tbl_akun_gl,id',
            'default_program_loans' => 'nullable|exists:tbl_program_kerja,id',
            'reimburse_tolerance_limit' => 'nullable|numeric|min:0',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        Cache::forget('app_settings'); // Clear cache if you use it for these settings

        return Redirect::back()->with([
            'message' => 'Pengaturan keuangan berhasil disimpan.',
            'type' => 'success'
        ]);
    }
}
