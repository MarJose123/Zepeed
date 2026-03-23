<?php

use App\Http\Controllers\MaintenanceWindowController;
use App\Http\Controllers\Provider\ProviderController;
use App\Http\Controllers\ProviderScheduleController;
use App\Http\Controllers\ScheduleController;

Route::middleware(['auth', 'verified'])->prefix('speedtest/')->name('speedtest.')->group(function () {

    Route::prefix('server/')->name('server.')->group(function () {
        Route::get('providers', [ProviderController::class, 'index'])
            ->name('providers.index');

        Route::patch('providers/{provider}', [ProviderController::class, 'update'])
            ->name('providers.update');

        Route::post('providers/{provider}/run-now', [ProviderController::class, 'runNow'])
            ->name('providers.run-now');

        Route::post('providers/{provider}/test', [ProviderController::class, 'test'])
            ->name('providers.test');
    });

    Route::prefix('schedules/')->name('schedules.')->group(function () {

        Route::get('/', [ScheduleController::class, 'index'])
            ->name('index');

        Route::patch('{providerSchedule}', [ProviderScheduleController::class, 'update'])
            ->name('update');
    });

    Route::prefix('maintenance/')->name('maintenance.')->group(function () {
        Route::post('/global-pause', [MaintenanceWindowController::class, 'toggleGlobalPause'])
            ->name('global-pause');
        Route::get('/', [MaintenanceWindowController::class, 'index'])
            ->name('index');
        Route::post('/', [MaintenanceWindowController::class, 'store'])
            ->name('store');
        Route::patch('/{maintenanceWindow}', [MaintenanceWindowController::class, 'update'])
            ->name('update');
        Route::delete('/{maintenanceWindow}', [MaintenanceWindowController::class, 'destroy'])
            ->name('destroy');

    });

});
