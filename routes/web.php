<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\PublicMetricsDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicDashboardController::class)
    ->middleware(['guest'])
    ->name('public.dashboard');

Route::get('/dashboard/metrics', PublicMetricsDashboardController::class)
    ->middleware(['guest'])
    ->name('public.metrics');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';
require __DIR__ . '/speedtest.php';
