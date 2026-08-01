<?php

use App\Http\Controllers\Api\V1\ProviderController;
use App\Http\Controllers\Api\V1\ProviderScheduleController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('providers')->group(static function () {

    // Schedules — static prefix registered before `{provider}` routes so a
    // future `GET /providers/{provider}` can never shadow `/providers/schedules`.
    Route::get('/schedules', [ProviderScheduleController::class, 'index'])
        ->middleware('ability:schedules:view,schedules:create,schedules:update,schedules:delete')
        ->name('api.provider-schedules.index');

    Route::post('/schedules', [ProviderScheduleController::class, 'store'])
        ->middleware('abilities:schedules:create')
        ->name('api.provider-schedules.store');

    Route::get('/schedules/{providerSchedule}', [ProviderScheduleController::class, 'show'])
        ->middleware('ability:schedules:view,schedules:create,schedules:update,schedules:delete')
        ->name('api.provider-schedules.show');

    Route::patch('/schedules/{providerSchedule}', [ProviderScheduleController::class, 'update'])
        ->middleware('abilities:schedules:update')
        ->name('api.provider-schedules.update');

    Route::delete('/schedules/{providerSchedule}', [ProviderScheduleController::class, 'destroy'])
        ->middleware('abilities:schedules:delete')
        ->name('api.provider-schedules.destroy');

    Route::get('/', [ProviderController::class, 'index'])
        ->middleware('ability:providers:view,providers:update')
        ->name('api.providers.index');

    Route::patch('/{provider}', [ProviderController::class, 'update'])
        ->middleware('abilities:providers:update')
        ->name('api.providers.update');

    Route::post('/{provider}/run-now', [ProviderController::class, 'runNow'])
        ->middleware('abilities:speedtest:run')
        ->name('api.providers.run-now');

});
