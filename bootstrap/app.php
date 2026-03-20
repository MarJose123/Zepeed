<?php

use App\Http\Middleware\HandleAppearanceMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Services\InertiaNotification;
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
