<?php

use App\Http\Controllers\Api\V1\WebhookController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('webhooks')->group(static function () {

    Route::get('/', [WebhookController::class, 'index'])
        ->middleware('ability:webhooks:view,webhooks:create,webhooks:update,webhooks:delete,webhooks:test')
        ->name('api.webhooks.index');

    Route::post('/', [WebhookController::class, 'store'])
        ->middleware('abilities:webhooks:create')
        ->name('api.webhooks.store');

    Route::get('/{webhook}', [WebhookController::class, 'show'])
        ->middleware('ability:webhooks:view,webhooks:create,webhooks:update,webhooks:delete,webhooks:test')
        ->name('api.webhooks.show');

    Route::patch('/{webhook}', [WebhookController::class, 'update'])
        ->middleware('abilities:webhooks:update')
        ->name('api.webhooks.update');

    Route::delete('/{webhook}', [WebhookController::class, 'destroy'])
        ->middleware('abilities:webhooks:delete')
        ->name('api.webhooks.destroy');

    Route::post('/{webhook}/test', [WebhookController::class, 'test'])
        ->middleware('abilities:webhooks:test')
        ->name('api.webhooks.test');

    Route::get('/{webhook}/deliveries', [WebhookController::class, 'deliveries'])
        ->middleware('ability:webhooks:view,webhooks:create,webhooks:update,webhooks:delete,webhooks:test')
        ->name('api.webhooks.deliveries');

});
