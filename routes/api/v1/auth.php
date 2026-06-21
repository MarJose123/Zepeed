<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:users-api')->prefix('auth')->group(static function () {
    Route::get('/user', [AuthController::class, 'user'])
        ->name('api.auth.user');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('api.auth.logout');
});
