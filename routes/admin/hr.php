<?php

// routes/admin/hr.php
// Modul: HR & Payroll — Karyawan, Cuti, Payroll, Pinjaman

use App\Http\Controllers\Admin\CompanyEventController;
use App\Http\Controllers\Admin\JenisCutiController;
use App\Http\Controllers\Admin\KaryawanController;
use App\Http\Controllers\Admin\RiwayatKarirController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\PinjamanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:Super Admin|HR Manager|HR Staff'])->group(function () {

    // Karyawan Management
    Route::resource('karyawan', KaryawanController::class)->withTrashed(['show', 'edit', 'update', 'destroy']);
    Route::post('karyawan/{karyawan}/generate-user', [KaryawanController::class, 'generateUser'])->name('karyawan.generate-user')->withTrashed();
    Route::post('karyawan/{karyawan}/contract-details', [KaryawanController::class, 'updateContractDetails'])->name('karyawan.contract-details.update')->withTrashed();
    Route::post('karyawan/{karyawan}/komponen-gaji', [KaryawanController::class, 'syncKomponenGaji'])->name('karyawan.komponen-gaji.sync')->withTrashed();
    Route::post('karyawan/{karyawan}/dokumen', [KaryawanController::class, 'uploadDokumen'])->name('karyawan.dokumen.upload')->withTrashed();
    Route::delete('karyawan/dokumen/{dokumen}', [KaryawanController::class, 'destroyDokumen'])->name('karyawan.dokumen.destroy');
    Route::get('karyawan/{karyawan}/contract-pdf', [KaryawanController::class, 'downloadContractPdf'])->name('karyawan.contract-pdf')->withTrashed();
    Route::get('karyawan/{karyawan}/contracts/{contract}/download', [KaryawanController::class, 'downloadContractVersionPdf'])->name('karyawan.contracts.download')->withTrashed();
    Route::post('karyawan/{karyawan}/archive-contract', [KaryawanController::class, 'archiveContractPdf'])->name('karyawan.archive-contract')->withTrashed();
    Route::get('karyawan/{karyawan}/contract-preview', [KaryawanController::class, 'getContractPreview'])->name('karyawan.contract-preview')->withTrashed();
    Route::post('karyawan/{karyawan}/reset-contract-content', [KaryawanController::class, 'resetContractContent'])->name('karyawan.reset-contract-content')->withTrashed();

    // Master Contract Templates
    Route::resource('contract-templates', \App\Http\Controllers\Admin\ContractTemplateController::class)->only(['index', 'update']);
    Route::post('contract-templates/{id?}/reset', [\App\Http\Controllers\Admin\ContractTemplateController::class, 'reset'])->name('contract-templates.reset');

    Route::resource('lokasi-kantor', \App\Http\Controllers\Admin\LokasiKantorController::class)->except(['create', 'show', 'edit']);
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

    // Company Events
    Route::resource('company-events', CompanyEventController::class);
});

// Pinjaman Karyawan (Dapat diakses oleh HR, Approver: Direktur/Finance/Manajer, serta Pengaju)
$pinjamanRoles = 'Super Admin|HR Manager|HR Staff|Direktur|Finance|Finance Manager|Finance Staff|Staf Finance|Manajer Departemen|Staf|Karyawan';
Route::middleware(["role:{$pinjamanRoles}"])->group(function () {
    Route::resource('pinjaman', PinjamanController::class);
    Route::post('pinjaman/{pinjaman}/action', [PinjamanController::class, 'action'])->name('pinjaman.action');
});
