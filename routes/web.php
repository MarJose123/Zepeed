<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PrometheusController;
use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\PublicMetricsDashboardController;
use App\Http\Middleware\PrometheusAccessMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicDashboardController::class)
    ->middleware(['guest'])
    ->name('public.dashboard');

Route::get('/dashboard/metrics', PublicMetricsDashboardController::class)
    ->middleware(['guest'])
    ->name('public.metrics');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/metrics', [PrometheusController::class, 'metrics'])
    ->middleware(PrometheusAccessMiddleware::class);

Route::get('exports/{exportRequest}/download', [ExportController::class, 'download'])
    ->middleware(['auth', 'verified'])
    ->name('exports.download');

require __DIR__ . '/settings.php';
require __DIR__ . '/speedtest.php';
