<?php

namespace App\Providers;

use App\Enums\SpeedtestServer;
use App\Events\Speedtest\SpeedtestExceptionEvent;
use App\Listeners\Speedtest\SendSpeedtestExceptionAlertListener;
use App\Models\Provider;
use App\Services\Speedtest\Contracts\SpeedtestServiceInterface;
use App\Services\Speedtest\FastcomService;
use App\Services\Speedtest\LibrespeedService;
use App\Services\Speedtest\OklaSpeedtestService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Override;

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
                    SpeedtestServer::Speedtest  => new OklaSpeedtestService($provider),
                    SpeedtestServer::Librespeed => new LibrespeedService($provider),
                    SpeedtestServer::Fastcom    => new FastcomService($provider),
                };
            }
        );
    }

    public function boot(): void
    {
        Event::listen(SpeedtestExceptionEvent::class, SendSpeedtestExceptionAlertListener::class);
    }
}
