<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:users-api')->group(static function () {
    Route::get('/auth/user', [AuthController::class, 'user'])
        ->name('api.auth.user');

    Route::post('/auth/logout', [AuthController::class, 'logout'])
        ->name('api.auth.logout');
});
