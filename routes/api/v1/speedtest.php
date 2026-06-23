<?php

use App\Http\Controllers\Api\V1\SpeedResultController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('speedtest')->group(static function () {

    Route::get('/results', [SpeedResultController::class, 'index'])
        ->name('api.speedtest-results.index');

});
