<?php

namespace App\Http\Middleware;

use App\Enums\AppearanceEnum;
use App\Enums\SeasonalThemeEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearanceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = null;
        $seasonalTheme = SeasonalThemeEnum::resolveActive();

        if (auth()->check() && auth()->hasUser()) {
            $appearance = auth()->user()->appearance;
            $seasonalTheme = SeasonalThemeEnum::tryFrom(auth()->user()->seasonal_theme ?? $seasonalTheme->value) ?? $seasonalTheme;
        }

        Inertia::share('appearance', [
            'mode'          => $appearance ?? AppearanceEnum::Light->label(),
            'seasonalTheme' => $seasonalTheme->value,
        ]);

        View::share('appearance', $appearance ?? $request->cookie('appearance'));
        View::share('seasonal_theme_class', $seasonalTheme->cssClass());

        return $next($request);
    }
}
