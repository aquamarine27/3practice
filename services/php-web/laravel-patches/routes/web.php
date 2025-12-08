<?php

use Illuminate\Support\Facades\Route;

// Главная и модули
Route::redirect('/', '/dashboard');

Route::view('/dashboard', 'dashboard.index')->name('dashboard');
Route::view('/iss',       'iss.index')      ->name('iss');
Route::view('/jwst',      'jwst.index')     ->name('jwst');
Route::view('/osdr',      'osdr.index')     ->name('osdr');
Route::view('/astro',     'astro.index')    ->name('astro');

// CMS страницы из БД
Route::get('/page/{slug}', [App\Http\Controllers\CmsController::class, 'page'])
    ->name('cms.page');

// API эндпоинты 
Route::prefix('api')->group(function () {
    Route::get('iss/last',  [App\Http\Controllers\ProxyController::class, 'last']);
    Route::get('iss/trend', [App\Http\Controllers\ProxyController::class, 'trend']);
    Route::get('jwst/feed', [App\Http\Controllers\JwstController::class, 'feed']);
    Route::get('astro/events', [App\Http\Controllers\AstroController::class, 'events']);
});