<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\ColorReadingController;
use App\Http\Controllers\AiRecommendationController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\NpkColorChartController;
use App\Http\Controllers\Admin\PhColorChartController;
use App\Http\Controllers\FarmerController;
use App\Http\Controllers\PhTestController;
use App\Http\Controllers\ParameterTestController;
use App\Http\Controllers\GeminiCropRecommendationController;
use App\Http\Controllers\HelpController;
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
    Route::get('/samples/{sample}/pdf',    [SampleController::class, 'pdf'])->name('samples.pdf');
    Route::post('/samples/{sample}/reset', [SampleController::class, 'reset'])->name('samples.reset');

    // pH test workflow (separate 2-step page)
    Route::get('/samples/{sample}/ph-test',       [PhTestController::class, 'show'])->name('ph-test.show');
    Route::post('/samples/{sample}/ph-test/reset',[PhTestController::class, 'reset'])->name('ph-test.reset');

    // N / P / K individual capture pages
    Route::get('/samples/{sample}/test/{parameter}', [ParameterTestController::class, 'show'])
        ->name('parameter-test.show')
        ->where('parameter', 'nitrogen|phosphorus|potassium');

    // API endpoints (called by JavaScript)
    Route::post('/api/color-readings',      [ColorReadingController::class,    'store'])->name('color-readings.store');
    Route::post('/api/ph-test/capture',     [PhTestController::class,          'capture'])->name('ph-test.capture');
    Route::post('/api/ph-test/recapture',   [PhTestController::class,          'recapture'])->name('ph-test.recapture');
    Route::post('/api/ai-recommendation',         [AiRecommendationController::class,       'generate'])->name('ai-recommendation.generate');
    Route::post('/api/gemini-crop-recommendations',[GeminiCropRecommendationController::class, 'generate'])->name('gemini-crop-recommendations.generate');

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

    // Help & Guidelines
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');

    // ── Admin-only ────────────────────────────────────────────────────────────
    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard',        [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/users',            [UserController::class, 'index'])->name('users');
        Route::post('/users',           [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}',     [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}',  [UserController::class, 'destroy'])->name('users.destroy');

        // pH Color Chart management
        Route::get('/ph-color-charts',                      [PhColorChartController::class, 'index'])->name('ph-color-charts');
        Route::post('/ph-color-charts',                     [PhColorChartController::class, 'store'])->name('ph-color-charts.store');
        Route::patch('/ph-color-charts/{phColorChart}',    [PhColorChartController::class, 'toggle'])->name('ph-color-charts.toggle');
        Route::delete('/ph-color-charts/{phColorChart}',   [PhColorChartController::class, 'destroy'])->name('ph-color-charts.destroy');

        // NPK Color Chart management
        Route::get('/npk-color-charts',                     [NpkColorChartController::class, 'index'])->name('npk-color-charts');
        Route::post('/npk-color-charts',                    [NpkColorChartController::class, 'store'])->name('npk-color-charts.store');
        Route::patch('/npk-color-charts/{npkColorChart}',  [NpkColorChartController::class, 'toggle'])->name('npk-color-charts.toggle');
        Route::delete('/npk-color-charts/{npkColorChart}', [NpkColorChartController::class, 'destroy'])->name('npk-color-charts.destroy');
    });
});
