<?php

use App\Http\Controllers\Api\V1\AlertRuleController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('alerts')->group(static function () {

    Route::get('/', [AlertRuleController::class, 'index'])
        ->middleware('ability:alerts:view,alerts:create,alerts:update,alerts:delete')
        ->name('api.alerts.index');

    Route::post('/', [AlertRuleController::class, 'store'])
        ->middleware('abilities:alerts:create')
        ->name('api.alerts.store');

    Route::get('/{alertRule}', [AlertRuleController::class, 'show'])
        ->middleware('ability:alerts:view,alerts:create,alerts:update,alerts:delete')
        ->name('api.alerts.show');

    Route::patch('/{alertRule}', [AlertRuleController::class, 'update'])
        ->middleware('abilities:alerts:update')
        ->name('api.alerts.update');

    Route::delete('/{alertRule}', [AlertRuleController::class, 'destroy'])
        ->middleware('abilities:alerts:delete')
        ->name('api.alerts.destroy');

    Route::post('/{alertRule}/toggle', [AlertRuleController::class, 'toggle'])
        ->middleware('abilities:alerts:update')
        ->name('api.alerts.toggle');

});
