<?php

use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\MailProviderController;
use App\Http\Controllers\MaintenanceWindowController;
use App\Http\Controllers\Provider\ProviderController;
use App\Http\Controllers\ProviderScheduleController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\WebhookController;

Route::middleware(['auth', 'verified'])->prefix('speedtest/')->name('speedtest.')->group(function () {

    // Settings Menu
    Route::prefix('settings/')->group(function () {

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

        Route::prefix('email-templates/')->name('email-templates.')->group(function () {
            Route::get('/', [EmailTemplateController::class, 'index'])
                ->name('index');
            Route::post('/', [EmailTemplateController::class, 'store'])
                ->name('store');
            Route::patch('/{emailTemplate}', [EmailTemplateController::class, 'update'])
                ->name('update');
            Route::delete('/{emailTemplate}', [EmailTemplateController::class, 'destroy'])
                ->name('destroy');
            Route::get('/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])
                ->name('preview');
            Route::post('/preview-raw', [EmailTemplateController::class, 'previewRaw'])->name('preview-raw');

        });

    });

    // Integration Menu
    Route::prefix('integration/')->name('integration.')->group(function () {
        Route::prefix('smtp/')
            ->name('smtp.')
            ->group(function () {
                Route::get('/', [MailProviderController::class, 'index'])->name('index');
                Route::post('/', [MailProviderController::class, 'store'])->name('store');
                Route::patch('/{mailProvider}', [MailProviderController::class, 'update'])->name('update');
                Route::delete('/{mailProvider}', [MailProviderController::class, 'destroy'])->name('destroy');
                Route::post('/reorder', [MailProviderController::class, 'reorder'])->name('reorder');
                Route::post('/{mailProvider}/test', [MailProviderController::class, 'test'])->name('test');
            });

        Route::prefix('webhooks/')
            ->name('webhooks.')
            ->group(function () {
                Route::get('/', [WebhookController::class, 'index'])->name('index');
                Route::post('/', [WebhookController::class, 'store'])->name('store');
                Route::patch('/{webhook}', [WebhookController::class, 'update'])->name('update');
                Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
                Route::post('/{webhook}/test', [WebhookController::class, 'test'])->name('test');
                Route::get('/{webhook}/deliveries', [WebhookController::class, 'deliveries'])->name('deliveries');
                Route::get('/{webhook}/deliveries/json', [WebhookController::class, 'deliveriesJson'])->name('deliveries.json');
            });

    });

});
