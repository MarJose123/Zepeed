<?php

namespace App\Services;

use App\Models\Prometheus;
use App\Services\Prometheus\PingMetricsBuilderService;
use App\Services\Prometheus\SpeedMetricsBuilderService;
use App\Services\Prometheus\SystemMetricsBuilderService;
use Illuminate\Support\Facades\Cache;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

class PrometheusExporterService
{
    private const string CACHE_PREFIX = 'zepeed:prometheus:metrics';

    public function __construct(
        private readonly SpeedMetricsBuilderService $speedBuilder,
        private readonly PingMetricsBuilderService $pingBuilder,
        private readonly SystemMetricsBuilderService $systemBuilder,
    ) {}

    /**
     * Build the full Prometheus text exposition output.
     *
     * Cache key encodes the three include_* boolean flags so any toggle
     * change immediately resolves to a different key, making the previous
     * cached output unreachable without depending on flush timing.
     *
     * CollectorRegistry is instantiated with registerDefaultMetrics=false
     * to suppress the automatic php_info gauge that promphp v2.x registers
     * unconditionally. We expose PHP version via zepeed_info instead.
     */
    public function build(Prometheus $config): string
    {
        return Cache::remember(
            $this->cacheKey($config),
            $config->cache_ttl,
            function () use ($config): string {
                $registry = new CollectorRegistry(new InMemory, false);

                if ($config->include_speed) {
                    $this->speedBuilder->register($registry, $config->providers ?? []);
                }

                if ($config->include_ping) {
                    $this->pingBuilder->register($registry);
                }

                if ($config->include_system) {
                    $this->systemBuilder->register($registry);
                }

                return new RenderTextFormat()->render($registry->getMetricFamilySamples());
            },
        );
    }

    /**
     * Invalidate the cached output for the given config state.
     *
     * Must be called with the config BEFORE making DB changes so the
     * old key is cleared. The new config state produces a different key
     * and populates fresh on the next scrape.
     */
    public function flush(Prometheus $config): void
    {
        Cache::forget($this->cacheKey($config));
    }

    /**
     * Derive a cache key that encodes the three metric group toggles.
     *
     * Changing any include_* flag produces a distinct key, eliminating
     * the stale-cache window that existed with the previous static key.
     */
    private function cacheKey(Prometheus $config): string
    {
        return sprintf(
            '%s:%d%d%d',
            self::CACHE_PREFIX,
            (int) $config->include_speed,
            (int) $config->include_ping,
            (int) $config->include_system,
        );
    }
}
