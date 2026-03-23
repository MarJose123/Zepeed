<?php

use App\Enums\QueueWorkerName;
use App\Http\Middleware\HandleAppearanceMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Jobs\RunSpeedtestJob;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Services\InertiaNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearanceMiddleware::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ])
            ->appendToPriorityList(StartSession::class, HandleInertiaRequests::class);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Load enabled providers that are fully configured
        // withoutOverlapping() is handled at the Job level via uniqueId()
        // so we don't need it here — avoids a cache dependency in bootstrap

        Provider::query()
            ->enabled()
            ->get()
            ->each(function (Provider $provider) use ($schedule) {
                // Fetch the Schedule record for this provider
                // Falls back to a safe default if no schedule row exists yet
                $providerSchedule = ProviderSchedule::forProvider(
                    $provider->slug
                );

                if (! $providerSchedule?->cron_expression) {
                    return; // no cron configured — skip silently
                }

                $schedule
                    ->job(new RunSpeedtestJob($provider), queue: QueueWorkerName::Speedtest->value)
                    ->cron($providerSchedule->cron_expression)
                    ->name("speedtest:{$provider->slug->value}")
                    ->withoutOverlapping(expiresAt: 10);
            });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (! app()->environment(['testing']) && in_array($response->getStatusCode(), [500, 503, 404, 403])) {
                $retryAfter = $response->headers->get('retry-after');
                $inertiaResponse = Inertia::render('ErrorPage', [
                    'status'     => $response->getStatusCode(),
                    'retryAfter' => $response->getStatusCode() === 503 ? $retryAfter : null,
                ])->toResponse($request);

                if ($retryAfter !== null) {
                    $inertiaResponse->headers->set('retry-after', $retryAfter);
                }

                return $inertiaResponse->setStatusCode($response->getStatusCode());
            }

            if ($response->getStatusCode() === 419) {
                InertiaNotification::make()
                    ->error()
                    ->title('Session expired.')
                    ->message('Your session has expired. Please refresh the page to continue.')
                    ->send();

                return redirect(to_route('login'));
            }

            return $response;
        });
    })->create();
