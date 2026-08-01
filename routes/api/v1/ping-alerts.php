<?php

use App\Http\Controllers\Api\V1\PingAlertRuleController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('ping-alerts')->group(static function () {

    Route::get('/', [PingAlertRuleController::class, 'index'])
        ->middleware('ability:ping-alerts:view,ping-alerts:create,ping-alerts:update,ping-alerts:delete')
        ->name('api.ping-alerts.index');

    Route::post('/', [PingAlertRuleController::class, 'store'])
        ->middleware('abilities:ping-alerts:create')
        ->name('api.ping-alerts.store');

    Route::get('/{pingAlertRule}', [PingAlertRuleController::class, 'show'])
        ->middleware('ability:ping-alerts:view,ping-alerts:create,ping-alerts:update,ping-alerts:delete')
        ->name('api.ping-alerts.show');

    Route::patch('/{pingAlertRule}', [PingAlertRuleController::class, 'update'])
        ->middleware('abilities:ping-alerts:update')
        ->name('api.ping-alerts.update');

    Route::delete('/{pingAlertRule}', [PingAlertRuleController::class, 'destroy'])
        ->middleware('abilities:ping-alerts:delete')
        ->name('api.ping-alerts.destroy');

    Route::post('/{pingAlertRule}/toggle', [PingAlertRuleController::class, 'toggle'])
        ->middleware('abilities:ping-alerts:update')
        ->name('api.ping-alerts.toggle');

});
