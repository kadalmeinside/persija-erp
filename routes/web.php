<?php

/**
 * routes/web.php
 *
 * Entry point untuk semua routes web.
 * Routes dipisah per modul di routes/admin/ agar mudah di-maintain.
 *
 * Struktur:
 *   routes/admin/system.php  → Super Admin: Users, Roles, Settings, Logs
 *   routes/admin/hr.php      → HR: Karyawan, Payroll, Cuti, Pinjaman
 *   routes/admin/finance.php → Finance: Budget, GL, Vendor, Invoice, Asset
 *   routes/admin/ess.php     → ESS: Pengajuan, Approval, Ticket, Kalender
 */

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\PinVerificationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PinController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\VerificationController as PublicVerificationController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Public Routes
// -----------------------------------------------------------------------
Route::get('/', [WelcomeController::class, 'index']);
Route::get('/verify/{uuid}', [PublicVerificationController::class, 'verify'])->name('public.verify');

// -----------------------------------------------------------------------
// Dashboard Redirect
// -----------------------------------------------------------------------
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// -----------------------------------------------------------------------
// Profile Routes
// -----------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// -----------------------------------------------------------------------
// Admin Routes — Prefix: /admin, Name: admin.*
// -----------------------------------------------------------------------
Route::prefix('admin')->name('admin.')->group(function () {

    // Auth
    Route::middleware('guest')->group(function () {
        Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AdminAuthenticatedSessionController::class, 'store']);
    });

    // Authenticated
    Route::middleware(['auth', 'verified'])->group(function () {

        // PIN Management
        Route::post('auth/verify-pin', [PinVerificationController::class, 'verify'])->name('auth.verify-pin');
        Route::prefix('pin')->name('pin.')->controller(PinController::class)->group(function () {
            Route::get('status', 'checkStatus')->name('status');
            Route::post('set', 'setPin')->name('set');
            Route::post('change', 'changePin')->name('change');
            Route::post('verify', 'verifyPin')->name('verify');
        });

        // Dashboard (semua authenticated user)
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Notifications
        Route::prefix('notifications')->name('notifications.')->controller(\App\Http\Controllers\Admin\NotificationController::class)->group(function () {
            Route::post('mark-all-read', 'markAllAsRead')->name('mark-all-read');
            Route::get('{id}/read', 'markAsRead')->name('read');
        });

        // PDF Tools
        Route::prefix('pdf-tools')->name('pdf-tools.')->controller(\App\Http\Controllers\Admin\PdfToolController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('merge', 'merge')->name('merge');
            Route::post('split', 'split')->name('split');
            Route::post('convert-image', 'convertImageToPdf')->name('convert-image');
            Route::post('convert-word', 'convertWordToPdf')->name('convert-word');
        });

        // Image Tools
        Route::prefix('image-tools')->name('image-tools.')->controller(\App\Http\Controllers\Admin\ImageToolController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('compress', 'compress')->name('compress');
            Route::post('upscale', 'upscale')->name('upscale');
        });

        // Modul Routes — dipisah per domain
        Route::get('documentation', function () {
            return inertia('Admin/Documentation/Index');
        })->name('documentation');

        require __DIR__ . '/admin/system.php';
        require __DIR__ . '/admin/hr.php';
        require __DIR__ . '/admin/finance.php';
        require __DIR__ . '/admin/ess.php';
    });
});

require __DIR__ . '/auth.php';

// -----------------------------------------------------------------------
// ADMS / iClock Routes (Fingerspot/ZKTeco)
// -----------------------------------------------------------------------
Route::any('iclock/cdata', [\App\Http\Controllers\Api\IClockController::class, 'cdata']);
Route::any('iclock/getrequest', [\App\Http\Controllers\Api\IClockController::class, 'getrequest']);
Route::any('iclock/devicecmd', [\App\Http\Controllers\Api\IClockController::class, 'devicecmd']);
Route::any('iclock/ping', [\App\Http\Controllers\Api\IClockController::class, 'ping']);