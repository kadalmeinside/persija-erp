<?php

// routes/admin/hr.php
// Modul: HR & Payroll — Karyawan, Cuti, Payroll, Pinjaman

use App\Http\Controllers\Admin\AbsensiSettingController;
use App\Http\Controllers\Admin\CompanyEventController;
use App\Http\Controllers\Admin\JenisCutiController;
use App\Http\Controllers\Admin\KaryawanController;
use App\Http\Controllers\Admin\RiwayatKarirController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\PinjamanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:Super Admin|HR Manager|HR Staff'])->group(function () {
    // Pengaturan Absensi Lokasi
    Route::get('absensi/settings', [AbsensiSettingController::class, 'index'])->name('absensi.settings');
    Route::post('absensi/settings', [AbsensiSettingController::class, 'update'])->name('absensi.settings.update');

    // Karyawan Management
    Route::resource('karyawan', KaryawanController::class)->withTrashed(['show', 'edit', 'update', 'destroy']);
    Route::post('karyawan/{karyawan}/riwayat-karir', [RiwayatKarirController::class, 'store'])->name('karyawan.riwayat-karir.store')->withTrashed();
    Route::delete('riwayat-karir/{riwayatKarir}', [RiwayatKarirController::class, 'destroy'])->name('karyawan.riwayat-karir.destroy');

    // Jenis Cuti
    Route::resource('jenis-cuti', JenisCutiController::class);

    // Payroll
    Route::resource('payrolls', PayrollController::class);
    Route::post('payrolls/{payroll}/approve', [PayrollController::class, 'approve'])->name('payrolls.approve');
    Route::post('payrolls/{payroll}/details', [PayrollController::class, 'storeDetail'])->name('payrolls.details.store');
    Route::put('payrolls/{payroll}/details/{detail}', [PayrollController::class, 'updateDetail'])->name('payrolls.details.update');
    Route::get('payrolls/{payroll}/details/{detail}/print', [PayrollController::class, 'printPayslip'])->name('payrolls.details.print');

    // Pinjaman Karyawan
    Route::resource('pinjaman', PinjamanController::class);
    Route::post('pinjaman/{pinjaman}/action', [PinjamanController::class, 'action'])->name('pinjaman.action');

    // Company Events
    Route::resource('company-events', CompanyEventController::class);
});
