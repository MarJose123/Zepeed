<?php

use App\Http\Controllers\Api\V1\ExportController;

Route::middleware(['auth:users-api', 'throttle:api-resources'])->prefix('exports')->group(static function () {

    Route::get('/', [ExportController::class, 'index'])
        ->middleware('ability:exports:view,exports:create')
        ->name('api.exports.index');

    Route::post('/', [ExportController::class, 'store'])
        ->middleware('abilities:exports:create')
        ->name('api.exports.store');

    Route::get('/{exportRequest}', [ExportController::class, 'show'])
        ->middleware('ability:exports:view,exports:create')
        ->name('api.exports.show');

    Route::get('/{exportRequest}/download', [ExportController::class, 'download'])
        ->middleware('ability:exports:view,exports:create')
        ->name('api.exports.download');

});
