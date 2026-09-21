<?php

// routes/admin/finance.php
// Modul: Finance & Accounting — Budget, GL, Vendors, Invoices, Assets

use App\Http\Controllers\Admin\AccountingPeriodController;
use App\Http\Controllers\Admin\AccountingReportController;
use App\Http\Controllers\Admin\AkunGlController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\BudgetController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DepartemenController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\InternalTransferController;
use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\KasBankController;
use App\Http\Controllers\Admin\PeriodeAnggaranController;
use App\Http\Controllers\Admin\PosAnggaranController;
use App\Http\Controllers\Admin\ProgramKerjaController;
use App\Http\Controllers\Admin\PettyCashReportController;
use App\Http\Controllers\Admin\TaxReportController;
use App\Http\Controllers\Admin\TaxTypeController;
use App\Http\Controllers\Admin\VendorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:Super Admin|Finance|Staf Finance|Finance Manager|Finance Staff'])->group(function () {
    // Master Data
    Route::resource('departemen', DepartemenController::class)
        ->parameters(['departemen' => 'departemen'])
        ->except(['create', 'edit', 'show']);
    Route::resource('vendors', VendorController::class);

    // Anggaran
    Route::resource('periode-anggaran', PeriodeAnggaranController::class)->except(['create', 'edit', 'show']);
    Route::resource('program-kerja', ProgramKerjaController::class)->except(['create', 'edit', 'show']);
    Route::resource('pos-anggaran', PosAnggaranController::class)->except(['create', 'edit', 'show']);
    Route::post('pos-anggaran/{posAnggaran}/archive', [PosAnggaranController::class, 'archive'])->name('pos-anggaran.archive');
    Route::post('pos-anggaran/{posAnggaran}/restore', [PosAnggaranController::class, 'restore'])->name('pos-anggaran.restore');
    Route::get('budget/options', [BudgetController::class, 'getBudgetOptions'])->name('budget.options');
    Route::post('budget/import', [BudgetController::class, 'import'])->name('budget.import');
    Route::post('budget/reset-period', [BudgetController::class, 'resetPeriod'])->name('budget.reset-period');
    Route::resource('budget', BudgetController::class);

    // GL & Kas
    Route::resource('kas-bank', KasBankController::class);
    Route::resource('akun-gl', AkunGlController::class)->except(['create', 'edit', 'show']);
    Route::post('akun-gl/{akunGl}/archive', [AkunGlController::class, 'archive'])->name('akun-gl.archive');
    Route::post('akun-gl/{akunGl}/restore', [AkunGlController::class, 'restore'])->name('akun-gl.restore');
    Route::resource('tax-types', TaxTypeController::class);

    // Tax Reporting
    Route::get('tax-report', [TaxReportController::class, 'index'])->name('tax-report.index');
    Route::get('tax-report/{id}', [TaxReportController::class, 'show'])->name('tax-report.show');
    Route::post('tax-report/upload-bukti-potong/{invoiceId}', [TaxReportController::class, 'uploadBuktiPotong'])
        ->name('tax-report.upload-bukti-potong');

    // Jurnal & Periode Akuntansi
    Route::resource('journals', JournalController::class)->middleware('check.period:tgl_jurnal');

    // New Period Closing Routes
    Route::get('period-closings', [\App\Http\Controllers\Admin\PeriodClosingController::class, 'index'])->name('period-closings.index');
    Route::post('period-closings/close', [\App\Http\Controllers\Admin\PeriodClosingController::class, 'close'])->name('period-closings.close');
    Route::post('period-closings/reopen', [\App\Http\Controllers\Admin\PeriodClosingController::class, 'reopen'])->name('period-closings.reopen');

    // Old accounting periods (deprecated, will be removed later)
    Route::resource('accounting-periods', AccountingPeriodController::class);
    Route::post('accounting-periods/{period}/close', [AccountingPeriodController::class, 'close'])->name('accounting-periods.close');
    Route::post('accounting-periods/{period}/reopen', [AccountingPeriodController::class, 'reopen'])->name('accounting-periods.reopen');
    Route::get('reports', [AccountingReportController::class, 'index'])->name('reports.index');

    // Revenue: Pelanggan & Invoice
    Route::resource('customers', CustomerController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/payment', [InvoiceController::class, 'storePayment'])
        ->name('invoices.payment.store')
        ->middleware('check.period:tgl_bayar');
    Route::put('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::post('invoices/{invoice}/approve', [InvoiceController::class, 'approve'])->name('invoices.approve');

    // Aset
    Route::resource('assets', AssetController::class);
    Route::post('assets/run-depreciation', [AssetController::class, 'runDepreciation'])->name('assets.run-depreciation');

    // Internal Transfer — nama otomatis mengikuti prefix group: admin.internal-transfers.*
    Route::resource('internal-transfers', InternalTransferController::class)
         ->only(['index', 'create', 'store', 'show']);
    Route::post('internal-transfers/{internalTransfer}/approve', [InternalTransferController::class, 'approve'])
         ->name('internal-transfers.approve');
    Route::post('internal-transfers/{internalTransfer}/cancel', [InternalTransferController::class, 'cancel'])
         ->name('internal-transfers.cancel');

    // Laporan Petty Cash
    Route::get('petty-cash-report', [PettyCashReportController::class, 'index'])
         ->name('petty-cash-report.index');
});
