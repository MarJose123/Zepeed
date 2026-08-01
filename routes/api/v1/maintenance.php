<?php

use App\Http\Controllers\Api\V1\MaintenanceController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('maintenance')->group(static function () {

    Route::get('/schedules', [MaintenanceController::class, 'index'])
        ->middleware('ability:maintenance:view,maintenance:create,maintenance:update,maintenance:delete')
        ->name('api.maintenance-schedule.index');

    Route::post('/schedules', [MaintenanceController::class, 'store'])
        ->middleware('abilities:maintenance:create')
        ->name('api.maintenance-schedule.store');

    Route::patch('/schedules/{maintenanceWindow}', [MaintenanceController::class, 'update'])
        ->middleware('abilities:maintenance:update')
        ->name('api.maintenance-schedule.update');

    Route::delete('/schedules/{maintenanceWindow}', [MaintenanceController::class, 'destroy'])
        ->middleware('abilities:maintenance:delete')
        ->name('api.maintenance-schedule.destroy');

    Route::post('/global-pause', [MaintenanceController::class, 'toggleGlobalPause'])
        ->middleware('abilities:maintenance:update')
        ->name('api.maintenance-schedule.global-pause');

});
