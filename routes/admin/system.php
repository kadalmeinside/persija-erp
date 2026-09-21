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

    // Finance Settings
    Route::get('finance-settings', [FinanceSettingController::class, 'index'])->name('finance-settings.index');
    Route::post('finance-settings', [FinanceSettingController::class, 'update'])->name('finance-settings.update');

    // Activity Logs
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
});
