<?php

use App\Http\Controllers\Api\V1\MaintenanceController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('maintenance')->group(static function () {

    Route::get('/schedules', [MaintenanceController::class, 'index'])
        ->name('api.maintenance-schedule.index');

});
