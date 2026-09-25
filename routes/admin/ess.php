<?php

// routes/admin/ess.php
// Modul: ESS (Employee Self Service) — Cuti, Pengajuan, Approval, Settlement, Tickets, Kalender
// Accessible by: semua role (karyawan, staf, finance, manajer, direktur, IT)

use App\Http\Controllers\Admin\ApprovalRuleController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\CutiController;
use App\Http\Controllers\Admin\HariLiburController;
use App\Http\Controllers\Admin\PengajuanController;
use App\Http\Controllers\Admin\Pengajuan\ApprovalController as PengajuanApprovalController;
use App\Http\Controllers\Admin\Pengajuan\PaymentController as PengajuanPaymentController;
use App\Http\Controllers\Admin\Pengajuan\ReportController as PengajuanReportController;
use App\Http\Controllers\Admin\SettlementController;
use App\Http\Controllers\Admin\TicketController;
use Illuminate\Support\Facades\Route;

$essRoles = 'Super Admin|Manajer Departemen|Finance|Staf|Direktur|Karyawan|Staf Finance|Finance Manager|Finance Staff|HR Staff|IT Support';

Route::middleware(["role:{$essRoles}"])->group(function () {

    // ----------------------------------------------------------------
    // Kalender & Hari Libur
    // ----------------------------------------------------------------
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::resource('hari-libur', HariLiburController::class);
    Route::post('hari-libur/fetch', [HariLiburController::class, 'fetch'])->name('hari-libur.fetch');

    // ----------------------------------------------------------------
    // Cuti (Leave)
    // ----------------------------------------------------------------
    Route::prefix('cuti')->name('cuti.')->controller(CutiController::class)->group(function () {
        Route::get('my-requests', 'myRequests')->name('my-requests');
        Route::get('approvals', 'approvals')->name('approvals');
        Route::get('management', 'management')->name('management');
        Route::post('generate', 'generate')->name('generate');
        Route::put('balances/{id}', 'updateBalance')->name('update-balance');
        Route::post('{cuti}/approve', 'approve')->name('approve');
        Route::get('{cuti}/print', 'print')->name('print');
        Route::get('{cuti}/export-pdf', 'exportPdf')->name('export-pdf');
    });
    Route::resource('cuti', CutiController::class)->except(['index', 'create']);

    // ----------------------------------------------------------------
    // Pengajuan (Payment Requests)
    // ----------------------------------------------------------------
    Route::prefix('pengajuan')->name('pengajuan.')->group(function () {

        // Laporan & History
        Route::get('my-requests', [PengajuanReportController::class, 'myRequests'])->name('my-requests');
        Route::get('{pengajuan}/print', [PengajuanReportController::class, 'print'])->name('print');
        Route::get('{pengajuan}/print-voucher', [PengajuanReportController::class, 'printVoucher'])->name('print-voucher');

        // Approval Flow
        Route::get('approvals', [PengajuanApprovalController::class, 'index'])->name('approvals');
        Route::post('{pengajuan}/action', [PengajuanApprovalController::class, 'action'])->name('action');

        // Pembayaran
        Route::get('payment-schedule', [PengajuanPaymentController::class, 'schedule'])->name('payment-schedule');
        Route::post('print-schedule', [PengajuanPaymentController::class, 'printSchedule'])->name('print-schedule');
        Route::post('{pengajuan}/pay', [PengajuanPaymentController::class, 'store'])->name('pay');

        // Helper Endpoints (AJAX)
        Route::controller(PengajuanController::class)->group(function () {
            Route::get('get-programs', 'getProgramsByDepartemen')->name('getProgramsByDepartemen');
            Route::get('get-accounts', 'getAccountsByProgram')->name('getAccountsByProgram');
            Route::get('get-tax-program', 'getTaxProgram')->name('getTaxProgram');
            Route::get('get-budget-balance', 'getBudgetBalance')->name('getBudgetBalance');
            Route::post('store-vendor', 'storeVendor')->name('storeVendor');
            Route::post('store-employee-bank', 'storeEmployeeBank')->name('storeEmployeeBank');
            Route::patch('{pengajuan}/cancel', 'cancel')->name('cancel');
            Route::post('{pengajuan}/toggle-open-coa', 'toggleOpenCoa')->name('toggle-open-coa');
        });
    });

    // Pengajuan Resource (harus di bawah prefix routes untuk hindari konflik)
    Route::resource('pengajuan', PengajuanController::class);

    // ----------------------------------------------------------------
    // Settlement (Pelaporan Pengeluaran)
    // ----------------------------------------------------------------
    Route::prefix('pengajuan/{pengajuan}/settlement')
        ->name('settlement.')
        ->controller(SettlementController::class)
        ->group(function () {
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('edit', 'edit')->name('edit');
            Route::post('update', 'update')->name('update');
            Route::post('verify', 'verify')->name('verify');
            Route::post('reject', 'reject')->name('reject');
            Route::get('print', 'print')->name('print');
        });

    // ----------------------------------------------------------------
    // Approval Rules
    // ----------------------------------------------------------------
    Route::resource('approval-rules', ApprovalRuleController::class);

    // ----------------------------------------------------------------
    // IT Support Tickets
    // ----------------------------------------------------------------
    Route::get('tickets/my-requests', [TicketController::class, 'myRequests'])->name('tickets.my-requests');
    Route::resource('tickets', TicketController::class);
    Route::post('tickets/{ticket}/comments', [TicketController::class, 'storeComment'])->name('tickets.comments.store');

    // ----------------------------------------------------------------
    // Task Management (Manajemen Tugas)
    // ----------------------------------------------------------------
    Route::post('tasks/{task}/status', [\App\Http\Controllers\TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
    Route::resource('tasks', \App\Http\Controllers\TaskController::class)->except(['create', 'show', 'edit']);
    // ----------------------------------------------------------------
    // Absensi (Attendance)
    // ----------------------------------------------------------------
    Route::prefix('absensi')->name('absensi.')->controller(\App\Http\Controllers\AttendanceController::class)->group(function () {
        Route::get('clock', 'clock')->name('clock');
        Route::post('clock', 'storeClock')->name('storeClock');
        Route::match(['get', 'post'], 'register-face', 'registerFace')->name('register-face');
        Route::get('my-attendance', 'myAttendance')->name('my-attendance');
        Route::get('rekap', 'index')->name('rekap'); // Untuk HR
        Route::get('print', 'print')->name('print');
        Route::get('export-pdf', 'exportPdf')->name('export-pdf');
    });
});
