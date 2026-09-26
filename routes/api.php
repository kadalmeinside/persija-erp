<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\CutiController;

/*
|--------------------------------------------------------------------------
| API Routes for Mobile Native App (Phase 1)
|--------------------------------------------------------------------------
*/

// API V1
Route::prefix('v1')->group(function () {
    
    // Public Routes
    Route::post('/login', [AuthController::class, 'login']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth & Profile
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'profile']);

        // Absensi (Attendance) Module
        Route::prefix('absensi')->group(function () {
            Route::get('/today', [AbsensiController::class, 'today']);
            Route::post('/clock-in', [AbsensiController::class, 'clockIn']);
            Route::post('/clock-out', [AbsensiController::class, 'clockOut']);
            Route::get('/history', [AbsensiController::class, 'history']);
        });

        // Cuti (Leave Request) Module
        Route::prefix('cuti')->group(function () {
            Route::get('/jenis', [CutiController::class, 'jenis']);
            Route::get('/balances', [CutiController::class, 'balances']);
            Route::get('/requests', [CutiController::class, 'requests']);
            Route::post('/request', [CutiController::class, 'submitRequest']);
            
            // Optional Manager Approvals
            Route::get('/approvals', [CutiController::class, 'pendingApprovals']);
            Route::post('/approve/{id}', [CutiController::class, 'approveRequest']);
        });
    });
});
