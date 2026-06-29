<?php

namespace App\Http\Middleware;

use App\Models\Prometheus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class PrometheusAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Checks in order: master switch → IP allowlist.
     * Each failure short-circuits with no information leak to scanners.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $config = Prometheus::config();

        if (! $config->is_enabled) {
            abort(404);
        }

        $allowedIps = $config->allowed_ips ?? [];

        if (! empty($allowedIps)) {
            $ip = (string) $request->ip();

            if (! IpUtils::checkIp($ip, $allowedIps)) {
                abort(403);
            }
        }

        return $next($request);
    }
}
