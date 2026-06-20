<?php

use App\Http\Controllers\Api\V1\AppController;

Route::middleware('auth:users-api')->prefix('app')->group(static function () {

    Route::get('/version', [AppController::class, 'show'])
        ->name('api.app-version.show');

});
