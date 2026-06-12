<?php

namespace App\Providers;

use App\Console\Commands\PruneSpeedResultsCommand;
use App\Console\Commands\PruneWebhookDeliveriesCommand;
use App\Enums\SpeedtestServer;
use App\Events\Speedtest\SpeedtestCompletedEvent;
use App\Events\Speedtest\SpeedtestExceptionEvent;
use App\Listeners\Speedtest\BroadcastDashboardRefreshListener;
use App\Listeners\Speedtest\SendSpeedtestExceptionAlertListener;
use App\Models\Provider;
use App\Models\Setting;
use App\Services\MailProviderService;
use App\Services\Speedtest\CloudflareSpeedService;
use App\Services\Speedtest\Contracts\SpeedtestServiceInterface;
use App\Services\Speedtest\FastcomService;
use App\Services\Speedtest\LibrespeedService;
use App\Services\Speedtest\OklaSpeedtestService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Override;
use Throwable;

class SpeedtestServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        // Contextual binding — resolves the correct concrete service
        // based on which a Provider model is passed to the container.
        //
        // Usage in Job:
        //   $service = app(SpeedtestServiceInterface::class, ['provider' => $provider]);
        //   $result  = $service->run();

        $this->app->bind(
            function ($app, array $parameters): SpeedtestServiceInterface {
                /** @var Provider $provider */
                $provider = $parameters['provider']
                    ?? throw new InvalidArgumentException(
                        'SpeedtestServiceInterface requires a Provider instance.'
                    );

                return match ($provider->slug) {
                    SpeedtestServer::Ookla               => new OklaSpeedtestService($provider),
                    SpeedtestServer::Librespeed          => new LibrespeedService($provider),
                    SpeedtestServer::Netflix             => new FastcomService($provider),
                    SpeedtestServer::Cloudflare          => new CloudflareSpeedService($provider),
                };
            }
        );
    }

    public function boot(): void
    {
        $this->dynamicMailers();

        $this->eventListeners();

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $this->scheduleSpeedtestPruning($schedule);
        });
    }

    protected function eventListeners(): void
    {
        Event::listen(SpeedtestExceptionEvent::class, SendSpeedtestExceptionAlertListener::class);
        Event::listen(SpeedtestCompletedEvent::class, BroadcastDashboardRefreshListener::class);
    }

    protected function dynamicMailers(): void
    {
        /**
         * Register dynamic failover mailer on every request
         * Only runs if the mail_providers table exists (avoids errors on fresh installations)
         */
        try {
            resolve(MailProviderService::class)->buildFailoverMailer();
        } catch (Throwable $th) {
            Log::error('Failed to build failover mailer.', [
                'exception' => $th,
            ]);
        }
    }

    /**
     * Register the pruning schedules.
     *
     * The prune time is driven by the stored `prune_schedule` setting so
     * the user's selection in General Settings → Data Retention is honoured
     * without redeploying or editing any files.
     *
     * Both commands are no-ops when pruning is disabled — the setting check
     * lives inside the command itself so the schedule entry is always safe
     * to register.
     */
    private function scheduleSpeedtestPruning(Schedule $schedule): void
    {
        $schedule
            ->command(PruneSpeedResultsCommand::class)
            ->lastDayOfMonth()
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->description('Prune old speed results — runs last day of month at 00:00');

        $schedule
            ->command(PruneWebhookDeliveriesCommand::class)
            ->lastDayOfMonth()
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->description('Prune old webhook delivery logs — runs last day of month at 00:00');
    }
}
