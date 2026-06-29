<?php

use App\Http\Controllers\Api\V1\AppController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('app')->group(static function () {

    Route::get('/version', [AppController::class, 'show'])
        ->name('api.app-version.show');

});

/**
 * Healthcheck
 *
 * @unauthenticated
 */
Route::get('/healthcheck', fn () => response()->json(['status' => 'ok']));
