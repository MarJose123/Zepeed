<?php

namespace App\Http\Controllers;

use App\Enums\SpeedtestServer;
use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\SpeedResult;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class PublicMetricsDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $range = (string) $request->query('range', '1d');
        $from = $request->query('from');
        $to = $request->query('to');

        [$start, $end, $granularity] = $this->resolveRange($range, $from, $to);

        $providers = $this->fetchActiveProviders($start, $end);
        $slugs = array_column($providers, 'slug');
        $pingTargets = $this->fetchActivePingTargets($start, $end);
        $targetIds = array_column($pingTargets, 'id');

        return Inertia::render('public/MetricsDashboard', [
            'range'             => $range,
            'from'              => $start->toDateString(),
            'to'                => $end->toDateString(),
            'granularity'       => $granularity,
            'providers'         => $providers,
            'pingTargets'       => $pingTargets,
            'speedSeries'       => $this->fetchSpeedSeries($start, $end, $granularity, $slugs),
            'pingSeries'        => $this->pivotMetric($start, $end, $granularity, static fn (object $row): ?float => $row->ping_ms !== null ? (float) $row->ping_ms : null, $slugs),
            'latencySeries'     => $this->pivotMetric($start, $end, $granularity, static fn (object $row): ?float => $row->ping_ms !== null ? (float) $row->ping_ms + (float) ($row->jitter_ms ?? 0) : null, $slugs),
            'jitterSeries'      => $this->pivotMetric($start, $end, $granularity, static fn (object $row): ?float => $row->jitter_ms !== null ? (float) $row->jitter_ms : null, $slugs),
            'networkPingSeries' => $this->fetchNetworkPingSeries($start, $end, $granularity, $targetIds),
        ]);
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable, string}
     */
    private function resolveRange(string $range, mixed $from, mixed $to): array
    {
        if ($range === 'custom' && $from && $to) {
            $start = CarbonImmutable::parse((string) $from)->startOfDay();
            $end = CarbonImmutable::parse((string) $to)->endOfDay();
            $days = (int) $start->diffInDays($end);

            return [$start, $end, match (true) {
                $days <= 2  => 'hourly',
                $days <= 31 => 'daily',
                default     => 'weekly',
            }];
        }

        return match ($range) {
            '7d'    => [CarbonImmutable::now()->subDays(7),  CarbonImmutable::now(), 'daily'],
            '30d'   => [CarbonImmutable::now()->subDays(30), CarbonImmutable::now(), 'daily'],
            default => [CarbonImmutable::now()->subDay(),    CarbonImmutable::now(), 'hourly'],
        };
    }

    /**
     * Human-readable label format — used on the XAxis display only.
     * Hourly range shows time; longer ranges include the date.
     */
    private function labelFormat(string $granularity): string
    {
        return match ($granularity) {
            'daily', 'weekly' => 'm/d H:i',
            default           => 'H:i',
        };
    }

    /**
     * @return array<int, array{slug: string, label: string}>
     */
    private function fetchActiveProviders(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $slugs = SpeedResult::query()
            ->where('status', 'success')
            ->whereBetween('measured_at', [$start, $end])
            ->toBase()
            ->select('provider_slug')
            ->distinct()
            ->pluck('provider_slug')
            ->all();

        $providers = [];

        foreach ($slugs as $slug) {
            $server = SpeedtestServer::tryFrom((string) $slug);
            if ($server !== null) {
                $providers[] = ['slug' => $server->value, 'label' => $server->label()];
            }
        }

        return $providers;
    }

    /**
     * Download + upload per individual result, pivoted by provider.
     * Keys: "{slug}_dl" and "{slug}_ul". No averaging.
     *
     * @param string[] $providerSlugs
     *
     * @return array<int, array<string, float|string|null>>
     */
    private function fetchSpeedSeries(
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $granularity,
        array $providerSlugs,
    ): array {
        if ($providerSlugs === []) {
            return [];
        }

        $fmt = $this->labelFormat($granularity);

        $rows = SpeedResult::query()
            ->where('status', 'success')
            ->whereBetween('measured_at', [$start, $end])
            ->whereIn('provider_slug', $providerSlugs)
            ->toBase()
            ->select(['measured_at', 'provider_slug', 'download_mbps', 'upload_mbps'])
            ->oldest('measured_at')
            ->get();

        /** @var array<string, array<string, float|string|null>> $buckets */
        $buckets = [];

        foreach ($rows as $row) {
            $key = (string) $row->measured_at;
            $slug = (string) $row->provider_slug;

            if (! isset($buckets[$key])) {
                $buckets[$key] = ['label' => Date::parse($row->measured_at)->format($fmt)];
            }

            $buckets[$key]["{$slug}_dl"] = $row->download_mbps !== null ? round((float) $row->download_mbps, 2) : null;
            $buckets[$key]["{$slug}_ul"] = $row->upload_mbps !== null ? round((float) $row->upload_mbps, 2) : null;
        }

        return array_values($buckets);
    }

    /**
     * @param string[]                      $providerSlugs
     * @param Closure(object): (float|null) $valueResolver
     *
     * @return array<int, array<string, float|string|null>>
     */
    private function pivotMetric(
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $granularity,
        Closure $valueResolver,
        array $providerSlugs,
    ): array {
        if ($providerSlugs === []) {
            return [];
        }

        $fmt = $this->labelFormat($granularity);

        $rows = SpeedResult::query()
            ->where('status', 'success')
            ->whereBetween('measured_at', [$start, $end])
            ->whereIn('provider_slug', $providerSlugs)
            ->toBase()
            ->select(['measured_at', 'provider_slug', 'ping_ms', 'jitter_ms'])
            ->oldest('measured_at')
            ->get();

        /** @var array<string, array<string, float|string|null>> $buckets */
        $buckets = [];

        foreach ($rows as $row) {
            $key = (string) $row->measured_at;

            if (! isset($buckets[$key])) {
                $buckets[$key] = ['label' => Date::parse($row->measured_at)->format($fmt)];
            }

            $value = $valueResolver($row);
            $buckets[$key][(string) $row->provider_slug] = $value !== null ? round($value, 2) : null;
        }

        return array_values($buckets);
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    private function fetchActivePingTargets(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return PingTarget::query()
            ->where('ping_targets.is_enabled', true)
            ->whereHas('results', static function (Builder $q) use ($start, $end): void {
                $q->whereIn('status', ['success', 'partial'])
                    ->whereNotNull('avg_ms')
                    ->whereBetween('measured_at', [$start, $end]);
            })
            ->orderBy('label')
            ->toBase()
            ->select(['ping_targets.id', 'ping_targets.label'])
            ->distinct()
            ->get()
            ->map(static fn (object $row): array => [
                'id'    => (string) $row->id,
                'label' => (string) $row->label,
            ])
            ->values()
            ->all();
    }

    /**
     * Individual ping_results rows pivoted by ping_target_id. No averaging.
     *
     * @param string[] $targetIds
     *
     * @return array<int, array<string, float|string|null>>
     */
    private function fetchNetworkPingSeries(
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $granularity,
        array $targetIds,
    ): array {
        if ($targetIds === []) {
            return [];
        }

        $fmt = $this->labelFormat($granularity);

        $rows = PingResult::query()
            ->whereIn('status', ['success', 'partial'])
            ->whereNotNull('avg_ms')
            ->whereBetween('measured_at', [$start, $end])
            ->whereIn('ping_target_id', $targetIds)
            ->toBase()
            ->select(['measured_at', 'ping_target_id', 'avg_ms'])
            ->oldest('measured_at')
            ->get();

        /** @var array<string, array<string, float|string|null>> $buckets */
        $buckets = [];

        foreach ($rows as $row) {
            $key = (string) $row->measured_at;

            if (! isset($buckets[$key])) {
                $buckets[$key] = ['label' => Date::parse($row->measured_at)->format($fmt)];
            }

            $buckets[$key][(string) $row->ping_target_id] = $row->avg_ms !== null
                ? round((float) $row->avg_ms, 2)
                : null;
        }

        return array_values($buckets);
    }
}
