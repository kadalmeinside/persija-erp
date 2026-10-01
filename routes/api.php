<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\CutiController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TaskController;

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
        Route::post('/update-password', [AuthController::class, 'updatePassword']);

        // Dashboard (BFF Pattern)
        Route::get('/dashboard/home', [DashboardController::class, 'home']);

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

        // Task Management Module
        Route::prefix('tasks')->group(function () {
            Route::get('/', [TaskController::class, 'index']); // active kanban
            Route::post('/', [TaskController::class, 'store']); // create new task
            Route::get('/history', [TaskController::class, 'history']); // all tasks (archived + active)
            Route::post('/{id}/status', [TaskController::class, 'updateStatus']); // change status
        });

        // Calendar Module
        Route::prefix('calendar')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\CalendarController::class, 'index']);
        });
    });
});
