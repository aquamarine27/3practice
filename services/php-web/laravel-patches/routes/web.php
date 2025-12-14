<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AstroController;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssController;
use App\Http\Controllers\OsdrController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\TelemetryController;
use App\Http\Middleware\RateLimit;

Route::get('/', function () {
    return redirect('/dashboard');
});

// Основные страницы приложения
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/osdr', [OsdrController::class, 'index']);
Route::get('/iss', [IssController::class, 'index']);
Route::get('/astro', [AstroController::class, 'index']);
Route::get('/cms', [CmsController::class, 'index']);
Route::get('/telemetry', [TelemetryController::class, 'index']);
Route::get('/telemetry/export/csv', [TelemetryController::class, 'exportCsv']);
Route::get('/telemetry/export/excel', [TelemetryController::class, 'exportExcel']);

// API-эндпоинты
Route::middleware(RateLimit::class)->group(function () {
    Route::get('/api/iss/last', [ProxyController::class, 'last']);
    Route::get('/api/iss/trend', [ProxyController::class, 'trend']);
    // JWST
    Route::get('/api/jwst/feed', [DashboardController::class, 'jwstFeed']);
    // ASTRO
    Route::get('/api/astro/events', [AstroController::class, 'events']);
});

// CMS
Route::get('/page/{slug}', [CmsController::class, 'page'])
    ->where('slug', '[a-zA-Z0-9_-]+');