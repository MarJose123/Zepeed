<?php

namespace App\Http\Middleware;

use App\Enums\AppearanceEnum;
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

        if (auth()->check() && auth()->hasUser()) {
            $appearance = auth()->user()->appearance;
        }

        Inertia::share('appearance', [
            'mode' => $appearance ?? AppearanceEnum::Light->label(),
        ]);

        View::share('appearance', $appearance ?? $request->cookie('appearance'));

        return $next($request);
    }
}
