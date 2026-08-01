<?php

use App\Http\Controllers\Api\V1\SpeedResultController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('speedtest')->group(static function () {

    Route::get('/results/latest', [SpeedResultController::class, 'latest'])
        ->middleware('ability:speedtest:view')
        ->name('api.speedtest-results.latest');

    Route::get('/results', [SpeedResultController::class, 'index'])
        ->middleware('ability:speedtest:view')
        ->name('api.speedtest-results.index');

});
