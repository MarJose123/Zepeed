<?php

use App\Console\Commands\SpeedtestRunCommand;
use Illuminate\Support\Facades\Artisan;

/*
 * Manually trigger a speedtest run for one or all providers.
 *
 * Run all enabled providers (queued by default):
 *   php artisan speedtest:run
 *
 * Run a specific provider:
 *   php artisan speedtest:run librespeed
 *
 * Run synchronously with inline metrics output:
 *   php artisan speedtest:run --sync
 *   php artisan speedtest:run speedtest --sync
 *
 * Explicit queue dispatch:
 *   php artisan speedtest:run --queue
 */
Artisan::command('speedtest:run {provider?} {--sync} {--queue}', function (?string $provider = null) {
    $this->call(SpeedtestRunCommand::class, array_filter([
        'provider' => $provider,
        '--sync'   => $this->option('sync'),
        '--queue'  => $this->option('queue'),
    ]));
})->purpose('Manually trigger a speedtest run for one or all providers');
