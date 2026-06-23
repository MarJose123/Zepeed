<?php

use App\Http\Controllers\Api\V1\PingResultController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('pings')->group(static function () {

    Route::get('/', [PingResultController::class, 'index'])
        ->name('api.pings.index');

});
