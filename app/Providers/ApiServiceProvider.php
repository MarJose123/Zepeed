<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        RateLimiter::for('api-login', static function (Request $request): Limit {
            $throttleKey = $request->user()
                ? Str::transliterate('api-login|' . $request->user()->id . '|' . $request->ip())
                : Str::transliterate('api-login|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey)
                ->response(static function () use ($throttleKey): JsonResponse {
                    $seconds = RateLimiter::availableIn($throttleKey);

                    return new JsonResponse([
                        'success' => false,
                        'code'    => 429,
                        'message' => "Too many requests. Please try again in {$seconds} seconds.",
                    ], 429, [
                        'Retry-After'       => $seconds,
                        'X-RateLimit-Limit' => 5,
                        'X-RateLimit-Reset' => now()->addSeconds($seconds)->getTimestamp(),
                    ]);
                });
        });

        RateLimiter::for('api-resources', static function (Request $request): Limit {
            $throttleKey = $request->user()
                ? Str::transliterate('api-resources|' . $request->user()->id . '|' . $request->ip())
                : Str::transliterate('api-resources|' . $request->ip());

            return Limit::perMinute(60)->by($throttleKey)
                ->response(static function () use ($throttleKey): JsonResponse {
                    $seconds = RateLimiter::availableIn($throttleKey);

                    return new JsonResponse([
                        'success' => false,
                        'code'    => 429,
                        'message' => "Too many requests. Please try again in {$seconds} seconds.",
                    ], 429, [
                        'Retry-After'       => $seconds,
                        'X-RateLimit-Limit' => 60,
                        'X-RateLimit-Reset' => now()->addSeconds($seconds)->getTimestamp(),
                    ]);
                });
        });

    }
}
