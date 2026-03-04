<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\ColorReadingController;
use App\Http\Controllers\AiRecommendationController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\FarmerController;
use Illuminate\Support\Facades\Route;

// Redirect root to login or dashboard
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'dashboard')
        : redirect()->route('login');
});

// ── Guest routes ─────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',    [LoginController::class,    'show'])->name('login');
    Route::post('/login',   [LoginController::class,    'login']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register',[RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ── Authenticated routes ──────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // User dashboard
    Route::get('/dashboard', [DashboardController::class, 'user'])->name('dashboard');

    // Soil samples
    Route::get('/samples',              [SampleController::class, 'index'])->name('samples.index');
    Route::get('/samples/create',       [SampleController::class, 'create'])->name('samples.create');
    Route::post('/samples',             [SampleController::class, 'store'])->name('samples.store');
    Route::get('/samples/{sample}',        [SampleController::class, 'show'])->name('samples.show');
    Route::get('/samples/{sample}/report', [SampleController::class, 'report'])->name('samples.report');
    Route::post('/samples/{sample}/reset', [SampleController::class, 'reset'])->name('samples.reset');

    // API endpoints (called by JavaScript)
    Route::post('/api/color-readings',      [ColorReadingController::class,    'store'])->name('color-readings.store');
    Route::post('/api/ai-recommendation',   [AiRecommendationController::class,'generate'])->name('ai-recommendation.generate');

    // Farmers (CRUD + CSV import + JSON for autocomplete)
    Route::get('/farmers',                [FarmerController::class, 'index'])->name('farmers.index');
    Route::get('/farmers/create',         [FarmerController::class, 'create'])->name('farmers.create');
    Route::post('/farmers',               [FarmerController::class, 'store'])->name('farmers.store');
    Route::get('/farmers/import',         [FarmerController::class, 'importForm'])->name('farmers.import');
    Route::post('/farmers/import',        [FarmerController::class, 'import'])->name('farmers.import.store');
    Route::get('/farmers/json',           [FarmerController::class, 'json'])->name('farmers.json');
    Route::get('/farmers/{farmer}/edit',  [FarmerController::class, 'edit'])->name('farmers.edit');
    Route::put('/farmers/{farmer}',       [FarmerController::class, 'update'])->name('farmers.update');
    Route::delete('/farmers/{farmer}',    [FarmerController::class, 'destroy'])->name('farmers.destroy');

    // Export
    Route::get('/export',         [ExportController::class, 'export'])->name('export');
    Route::get('/export/phase2',  [ExportController::class, 'exportPhase2'])->name('export.phase2');

    // ── Admin-only ────────────────────────────────────────────────────────────
    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard',        [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/users',            [UserController::class, 'index'])->name('users');
        Route::post('/users',           [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}',     [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}',  [UserController::class, 'destroy'])->name('users.destroy');
    });
});
