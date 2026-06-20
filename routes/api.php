<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/v1/auth.php';
    require __DIR__ . '/api/v1/ping.php';
    require __DIR__ . '/api/v1/app.php';
    require __DIR__ . '/api/v1/provider.php';
    require __DIR__ . '/api/v1/speedtest.php';
    require __DIR__ . '/api/v1/maintenance.php';
});
