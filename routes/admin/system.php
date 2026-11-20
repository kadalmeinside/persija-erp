<?php

// routes/admin/system.php
// Modul: Super Admin Only — Users, Roles, Permissions, Settings, Logs

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\FinanceSettingController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:Super Admin'])->group(function () {
    // Users & Roles
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/test-email', [SettingsController::class, 'sendTestEmail'])->name('settings.test-email');
    Route::post('settings/test-notification', [SettingsController::class, 'sendTestNotification'])->name('settings.test-notification');

    // Finance Settings
    Route::get('finance-settings', [FinanceSettingController::class, 'index'])->name('finance-settings.index');
    Route::post('finance-settings', [FinanceSettingController::class, 'update'])->name('finance-settings.update');

    // Activity Logs
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Governance: Immutable Audit, Reconciliation, Delegations
    Route::prefix('governance')->name('governance.')->controller(\App\Http\Controllers\Admin\GovernanceController::class)->group(function () {
        Route::get('audit-logs', 'auditLogs')->name('audit-logs');
        Route::post('reconcile', 'reconcile')->name('reconcile');
        Route::post('delegations', 'storeDelegation')->name('delegations.store');
    });
});
