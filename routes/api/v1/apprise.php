<?php

use App\Http\Controllers\Api\V1\AppriseController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('apprise')->group(static function () {

    Route::get('/', [AppriseController::class, 'index'])
        ->middleware('ability:apprise:view,apprise:create,apprise:update,apprise:delete,apprise:test')
        ->name('api.apprise.index');

    Route::post('/', [AppriseController::class, 'store'])
        ->middleware('abilities:apprise:create')
        ->name('api.apprise.store');

    Route::get('/{apprise}', [AppriseController::class, 'show'])
        ->middleware('ability:apprise:view,apprise:create,apprise:update,apprise:delete,apprise:test')
        ->name('api.apprise.show');

    Route::patch('/{apprise}', [AppriseController::class, 'update'])
        ->middleware('abilities:apprise:update')
        ->name('api.apprise.update');

    Route::delete('/{apprise}', [AppriseController::class, 'destroy'])
        ->middleware('abilities:apprise:delete')
        ->name('api.apprise.destroy');

    Route::post('/{apprise}/test', [AppriseController::class, 'test'])
        ->middleware('abilities:apprise:test')
        ->name('api.apprise.test');

});
