<?php

use App\Http\Controllers\Api\V1\ProviderController;
use App\Http\Controllers\Api\V1\ProviderScheduleController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('providers')->group(static function () {

    Route::get('/', [ProviderController::class, 'index'])
        ->name('api.providers.index');

    Route::get('/schedules', [ProviderScheduleController::class, 'index'])
        ->name('api.provider-schedules.index');

});
