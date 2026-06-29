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
    public function __construct(
        private readonly SpeedMetricsBuilderService $speedBuilder,
        private readonly PingMetricsBuilderService $pingBuilder,
        private readonly SystemMetricsBuilderService $systemBuilder,
    ) {}

    /**
     * Build the full Prometheus text exposition output.
     *
     * The rendered string is cached using Laravel's native cache driver for
     * the configured TTL so repeated scrapes within the window do not hit the DB.
     */
    public function build(Prometheus $config): string
    {
        return Cache::remember(
            'zepeed:prometheus:metrics',
            $config->cache_ttl,
            function () use ($config): string {
                $registry = new CollectorRegistry(new InMemory);

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
     * Invalidate the cached metrics output immediately.
     */
    public function flush(): void
    {
        Cache::forget('zepeed:prometheus:metrics');
    }
}
