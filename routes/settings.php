<?php

use App\Http\Controllers\Account\Settings\AppearanceController;
use App\Http\Controllers\Account\Settings\PasswordController;
use App\Http\Controllers\Account\Settings\ProfileController;
use App\Http\Controllers\Account\Settings\SessionController;
use App\Http\Controllers\Account\Settings\TwoFactorAuthenticationController;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::patch('/appearance', [AppearanceController::class, 'update'])->name('appearance.update');

    Route::middleware(['verified'])->group(function () {

        Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

        Route::put('settings/password', [PasswordController::class, 'update'])
            ->middleware('throttle:change-password')
            ->name('user-password.update');

        Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
            ->name('two-factor.show');

        Route::get('settings/sessions', [SessionController::class, 'edit'])
            ->name('user-sessions.edit');

        Route::delete('settings/sessions', [SessionController::class, 'destroy'])
            ->name('user-sessions.destroy');

        Route::delete('settings/session', [SessionController::class, 'revokeSession'])
            ->name('user-sessions.revoke');

    });

});
