<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\CutiController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ApprovalCenterController;
use App\Http\Controllers\Api\KaryawanController;

/*
|--------------------------------------------------------------------------
| API Routes for Mobile Native App (Phase 1)
|--------------------------------------------------------------------------
*/

// API V1
Route::prefix('v1')->group(function () {
    
    // Public Routes (Rate limited: 5 requests per minute)
    Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth & Profile
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'profile']);
        Route::post('/user/register-face', [AuthController::class, 'registerFace']);
        Route::post('/update-password', [AuthController::class, 'updatePassword']);

        // PIN Management
        Route::prefix('pin')->group(function () {
            Route::get('/status', [\App\Http\Controllers\Admin\PinController::class, 'checkStatus']);
            Route::post('/set', [\App\Http\Controllers\Admin\PinController::class, 'setPin']);
            Route::post('/change', [\App\Http\Controllers\Admin\PinController::class, 'changePin']);
            Route::post('/verify', [\App\Http\Controllers\Admin\PinController::class, 'verifyPin']);
        });

        // Dashboard (BFF Pattern)
        Route::get('/dashboard/home', [DashboardController::class, 'home']);
        // Absensi (Attendance) Module — Accessible by Freelance
        Route::prefix('absensi')->group(function () {
            Route::get('/today', [AbsensiController::class, 'today']);
            Route::get('/absent-today', [AbsensiController::class, 'absentToday']);
            
            // Rate limit attendance actions: 10 requests per minute
            Route::middleware('throttle:10,1')->group(function () {
                Route::post('/challenge', [AbsensiController::class, 'challenge']);
                Route::post('/clock-in', [AbsensiController::class, 'clockIn']);
                Route::post('/clock-out', [AbsensiController::class, 'clockOut']);
            });
            
            Route::get('/history', [AbsensiController::class, 'history']);
        });

        // Notifications Module — Accessible by Freelance
        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
            Route::post('/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
            Route::get('/preferences', [\App\Http\Controllers\Api\NotificationController::class, 'getPreferences']);
            Route::post('/preferences', [\App\Http\Controllers\Api\NotificationController::class, 'updatePreferences']);
            Route::post('/device-token', [\App\Http\Controllers\Api\NotificationController::class, 'registerDeviceToken']);
            Route::post('/simulate', [\App\Http\Controllers\Api\NotificationController::class, 'simulate']);
        });

        // ── Modules Blocked for Role Freelance ─────────────────────────────────
        Route::middleware('not_freelance')->group(function () {
            // Approvals
            Route::get('/approvals', [ApprovalCenterController::class, 'index']);
            Route::get('/approvals/{approval}', [ApprovalCenterController::class, 'show']);
            Route::post('/approvals/{approval}/action', [ApprovalCenterController::class, 'action']);

            // Cuti (Leave Request) Module
            Route::prefix('cuti')->group(function () {
                Route::get('/jenis', [CutiController::class, 'jenis']);
                Route::get('/balances', [CutiController::class, 'balances']);
                Route::get('/requests', [CutiController::class, 'requests']);
                Route::get('/all', [CutiController::class, 'allRequests']);
                Route::post('/request', [CutiController::class, 'submitRequest']);
                Route::post('/cancel/{id}', [CutiController::class, 'cancelRequest']);
                
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
                Route::post('/events', [\App\Http\Controllers\Api\CalendarController::class, 'store']);
            });

            // ESS (Employee Self-Service) Module
            Route::prefix('ess')->group(function () {
                // Koreksi Absensi
                Route::get('/attendance-correction', [\App\Http\Controllers\Api\EssController::class, 'getAttendanceCorrections']);
                Route::post('/attendance-correction', [\App\Http\Controllers\Api\EssController::class, 'submitAttendanceCorrection']);

                // Dinas Luar
                Route::get('/business-trip', [\App\Http\Controllers\Api\EssController::class, 'getBusinessTrips']);
                Route::post('/business-trip', [\App\Http\Controllers\Api\EssController::class, 'submitBusinessTrip']);

                // Slip Gaji (Payslip Read-Only)
                Route::get('/payslips', [\App\Http\Controllers\Api\EssController::class, 'getPayslips']);
                Route::get('/payslips/{id}', [\App\Http\Controllers\Api\EssController::class, 'getPayslipDetail']);

                // Reimbursement
                Route::get('/reimbursements', [\App\Http\Controllers\Api\EssController::class, 'getReimbursements']);
                Route::post('/reimbursements', [\App\Http\Controllers\Api\EssController::class, 'submitReimbursement']);
            });

            // Karyawan Module
            Route::get('/karyawan', [KaryawanController::class, 'index']);
        });
    });
});
