<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;

use App\Http\Controllers\DisplayController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\PengajuanController;


Route::get('/', function (Request $request) {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'userIp' => $request->ip(),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION ,
    ]);
});

Route::get('/display', [DisplayController::class, 'index'])->name('display.index');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])
                ->middleware('guest') // Hanya bisa diakses oleh guest (belum login)
                ->name('login');

    Route::post('login', [AdminAuthenticatedSessionController::class, 'store'])
                ->middleware('guest');

    Route::middleware(['auth', 'verified'])->group(function () {
        
        Route::middleware(['role:Super Admin'])->group(function () {
            Route::resource('roles', RoleController::class);
            Route::resource('permissions', PermissionController::class);
            Route::resource('users', UserController::class);
            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
        });

        Route::middleware(['role:Super Admin|Manajer Departemen|Staf Finance|Staf'])->group(function() {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('pengajuan/get-budget-balance', [PengajuanController::class, 'getBudgetBalance'])
                ->name('pengajuan.getBudgetBalance');
            Route::resource('pengajuan', PengajuanController::class);
            
        });

    });
});


require __DIR__.'/auth.php';
