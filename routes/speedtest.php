<?php

use App\Http\Controllers\AlertRuleController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GeneralSettingsController;
use App\Http\Controllers\MailProviderController;
use App\Http\Controllers\MaintenanceWindowController;
use App\Http\Controllers\PingAlertRuleController;
use App\Http\Controllers\PingResultController;
use App\Http\Controllers\PingTargetController;
use App\Http\Controllers\PrometheusController;
use App\Http\Controllers\Provider\ProviderController;
use App\Http\Controllers\ProviderScheduleController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SpeedResultController;
use App\Http\Controllers\WebhookController;

Route::middleware(['auth', 'verified'])->prefix('speedtest/')->name('speedtest.')->group(static function () {

    // Results Menu
    Route::prefix('results/')->name('results.')->group(static function () {
        Route::get('download', [SpeedResultController::class, 'download'])->name('download');
        Route::get('upload', [SpeedResultController::class, 'upload'])->name('upload');
        Route::get('latency', [SpeedResultController::class, 'ping'])->name('latency');
        // export
        Route::post('download/export', [ExportController::class, 'storeSpeedDownload'])->name('download.export');
        Route::post('upload/export', [ExportController::class, 'storeSpeedUpload'])->name('upload.export');
        Route::post('latency/export', [ExportController::class, 'storeSpeedLatency'])->name('latency.export');
    });

    // Settings Menu
    Route::prefix('settings/')->group(static function () {

        Route::prefix('server/')->name('server.')->group(static function () {
            Route::get('providers', [ProviderController::class, 'index'])
                ->name('providers.index');

            Route::patch('providers/{provider}', [ProviderController::class, 'update'])
                ->name('providers.update');

            Route::post('providers/{provider}/run-now', [ProviderController::class, 'runNow'])
                ->name('providers.run-now');

            Route::post('providers/{provider}/test', [ProviderController::class, 'test'])
                ->name('providers.test');

            Route::delete('providers/{provider}/test/{testSessionId}', [ProviderController::class, 'cancelTest'])
                ->name('providers.test.cancel');

            Route::get('ookla/servers/search', [ProviderController::class, 'searchOoklaServers'])
                ->name('ookla.servers.search');

            Route::post('ookla/servers/cache/refresh', [ProviderController::class, 'refreshOoklaServersCache'])
                ->name('ookla.servers.cache.refresh');
        });

        Route::prefix('schedules/')->name('schedules.')->group(static function () {

            Route::get('/', [ScheduleController::class, 'index'])
                ->name('index');

            Route::post('/', [ProviderScheduleController::class, 'store'])
                ->name('store');

            Route::patch('{providerSchedule}', [ProviderScheduleController::class, 'update'])
                ->name('update');

            Route::delete('{providerSchedule}', [ProviderScheduleController::class, 'destroy'])
                ->name('destroy');
        });

        Route::prefix('maintenance/')->name('maintenance.')->group(static function () {
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

        Route::prefix('email-templates/')->name('email-templates.')->group(static function () {
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

        Route::prefix('alert-rules/')
            ->name('alert-rules.')
            ->group(static function () {
                Route::get('/', [AlertRuleController::class, 'index'])->name('index');
                Route::post('/', [AlertRuleController::class, 'store'])->name('store');
                Route::patch('/{alertRule}', [AlertRuleController::class, 'update'])->name('update');
                Route::delete('/{alertRule}', [AlertRuleController::class, 'destroy'])->name('destroy');
                Route::post('/{alertRule}/toggle', [AlertRuleController::class, 'toggle'])->name('toggle');
            });

        Route::prefix('general-settings/')
            ->name('general-settings.')
            ->group(static function (): void {

                Route::get('general', [GeneralSettingsController::class, 'edit'])
                    ->name('edit');

                Route::patch('general', [GeneralSettingsController::class, 'update'])
                    ->name('update');

                Route::post('general/danger', [GeneralSettingsController::class, 'danger'])
                    ->name('danger');

                Route::post('general/cache', [GeneralSettingsController::class, 'clearCache'])
                    ->name('cache.clear');

            });

    });

    // Integration Menu
    Route::prefix('integration/')->name('integration.')->group(static function () {
        Route::prefix('smtp/')
            ->name('smtp.')
            ->group(static function () {
                Route::get('/', [MailProviderController::class, 'index'])->name('index');
                Route::post('/', [MailProviderController::class, 'store'])->name('store');
                Route::patch('/{mailProvider}', [MailProviderController::class, 'update'])->name('update');
                Route::delete('/{mailProvider}', [MailProviderController::class, 'destroy'])->name('destroy');
                Route::post('/reorder', [MailProviderController::class, 'reorder'])->name('reorder');
                Route::post('/{mailProvider}/test', [MailProviderController::class, 'test'])->name('test');
            });

        Route::prefix('webhooks/')
            ->name('webhooks.')
            ->group(static function () {
                Route::get('/', [WebhookController::class, 'index'])->name('index');
                Route::post('/', [WebhookController::class, 'store'])->name('store');
                Route::patch('/{webhook}', [WebhookController::class, 'update'])->name('update');
                Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
                Route::post('/{webhook}/test', [WebhookController::class, 'test'])->name('test');
                Route::get('/{webhook}/deliveries', [WebhookController::class, 'deliveries'])->name('deliveries');
                Route::get('/{webhook}/deliveries/json', [WebhookController::class, 'deliveriesJson'])->name('deliveries.json');
            });

        Route::prefix('prometheus/')->name('prometheus.')->group(static function () {
            Route::get('/', [PrometheusController::class, 'index'])->name('index');
            Route::post('/', [PrometheusController::class, 'update'])->name('update');
            Route::post('flush', [PrometheusController::class, 'flushCache'])->name('cache.flush');
        });

    });

    // Network Menu
    Route::prefix('network/')->name('network.')->group(static function () {

        Route::prefix('ping-targets/')->name('ping-targets.')->group(static function () {
            Route::get('/', [PingTargetController::class, 'index'])->name('index');
            Route::post('/', [PingTargetController::class, 'store'])->name('store');
            Route::patch('{pingTarget}', [PingTargetController::class, 'update'])->name('update');
            Route::delete('{pingTarget}', [PingTargetController::class, 'destroy'])->name('destroy');
            Route::post('{pingTarget}/run-now', [PingTargetController::class, 'runNow'])->name('run-now');
        });

        Route::prefix('ping-results/')->name('ping-results.')->group(static function () {
            Route::get('/', [PingResultController::class, 'index'])->name('index');
            // export
            Route::post('export', [ExportController::class, 'storePingResult'])
                ->name('ping-results.export');
        });

        Route::prefix('ping-alerts/')->name('ping-alerts.')->group(static function () {
            Route::get('/', [PingAlertRuleController::class, 'index'])->name('index');
            Route::post('/', [PingAlertRuleController::class, 'store'])->name('store');
            Route::patch('{pingAlertRule}', [PingAlertRuleController::class, 'update'])->name('update');
            Route::delete('{pingAlertRule}', [PingAlertRuleController::class, 'destroy'])->name('destroy');
            Route::post('{pingAlertRule}/toggle', [PingAlertRuleController::class, 'toggle'])->name('toggle');
        });
    });

});
