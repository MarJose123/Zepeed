<?php

namespace App\Providers;

use App\Console\Commands\PruneSpeedResultsCommand;
use App\Console\Commands\PruneWebhookDeliveriesCommand;
use App\Enums\SpeedtestServer;
use App\Events\Speedtest\SpeedtestExceptionEvent;
use App\Listeners\Speedtest\SendSpeedtestExceptionAlertListener;
use App\Models\Provider;
use App\Models\Setting;
use App\Services\MailProviderService;
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
                    SpeedtestServer::Speedtest           => new OklaSpeedtestService($provider),
                    SpeedtestServer::Librespeed          => new LibrespeedService($provider),
                    SpeedtestServer::NetflixSpeedtest    => new FastcomService($provider),
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
        $frequency = (string) Setting::get('prune_frequency', 'daily');
        $hour = max(0, min(23, (int) Setting::get('prune_hour', 2)));
        $dayOfWeek = max(0, min(6, (int) Setting::get('prune_day_of_week', 0)));
        $dayOfMonth = max(1, min(28, (int) Setting::get('prune_day_of_month', 1)));

        $time = str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';

        $speedResultJob = $schedule
            ->command(PruneSpeedResultsCommand::class)
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->description('Prune old speed results');

        $webhookJob = $schedule
            ->command(PruneWebhookDeliveriesCommand::class)
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->description('Prune old webhook delivery logs');

        match ($frequency) {
            'weekly'  => ($speedResultJob->weeklyOn($dayOfWeek, $time)
                && $webhookJob->weeklyOn($dayOfWeek, $time)),
            'monthly' => ($speedResultJob->monthlyOn($dayOfMonth, $time)
                && $webhookJob->monthlyOn($dayOfMonth, $time)),
            default   => ($speedResultJob->dailyAt($time)
                && $webhookJob->dailyAt($time)),
        };
    }
}
