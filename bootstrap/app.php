<?php

use App\Enums\QueueWorkerName;
use App\Exceptions\ApiException;
use App\Http\Middleware\HandleAppearanceMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PreventRequestsDuringMaintenanceMiddleware;
use App\Jobs\RunPingTestJob;
use App\Jobs\RunSpeedtestJob;
use App\Models\PingTarget;
use App\Models\ProviderSchedule;
use App\Services\InertiaNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Request as RequestAlias;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(static function (Middleware $middleware): void {

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->replaceInGroup('web', PreventRequestsDuringMaintenance::class, PreventRequestsDuringMaintenanceMiddleware::class);

        $middleware->trustProxies(
            at: '*',
            headers: RequestAlias::HEADER_X_FORWARDED_FOR
        );

        $middleware->web(append: [
            HandleAppearanceMiddleware::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ])
            ->appendToPriorityList(StartSession::class, HandleInertiaRequests::class);
    })
    ->withSchedule(static function (Schedule $schedule) {

        try {
            // Load enabled providers that are fully configured
            // withoutOverlapping() is handled at the Job level via uniqueId()
            // so we don't need it here — avoids a cache dependency in bootstrap
            ProviderSchedule::query()
                ->enabled()
                ->whereHas('provider', static fn ($q) => $q->enabled())
                ->get()
                ->each(static function (ProviderSchedule $providerSchedule) use ($schedule) {
                    if (! $providerSchedule->cron_expression) {
                        return;
                    }

                    $schedule
                        ->job(
                            new RunSpeedtestJob($providerSchedule->provider),
                            queue: QueueWorkerName::Speedtest->value,
                        )
                        ->cron($providerSchedule->cron_expression)
                        ->name("speedtest:{$providerSchedule->provider_slug->value}:{$providerSchedule->id}")
                        ->withoutOverlapping(expiresAt: 10);
                });

            // Network — Ping Targets (every minute)
            PingTarget::query()
                ->enabled()
                ->get()
                ->each(static function (PingTarget $target) use ($schedule): void {
                    $schedule
                        ->job(
                            new RunPingTestJob($target),
                            queue: QueueWorkerName::Ping->value,
                        )
                        ->everyMinute()
                        ->name("ping-target:{$target->id}")
                        ->description(sprintf('Queuing %s(%s) for ping test', $target->label, $target->host))
                        ->withoutOverlapping(expiresAt: 2);
                });
        } catch (Throwable) {
            // Silently skip schedule registration if database is unavailable.
            // This allows composer install and other bootstrap operations to proceed
            // before migrations have run or during CI environments.
        }

    })
    ->withExceptions(static function (Exceptions $exceptions): void {

        $exceptions->renderable(static function (Throwable $throwable, Request $request) {
            if ($request->is('api/*') && $request->wantsJson() || $request->expectsJson()) {
                return app(ApiException::class)->renderApiException($throwable);
            }
        });

        $exceptions->respond(static function (Response $response, Throwable $exception, Request $request) {
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
