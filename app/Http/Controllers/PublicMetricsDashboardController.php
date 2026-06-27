<?php

namespace App\Http\Controllers;

use App\Enums\SpeedtestServer;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'pingSeries'        => $this->pivotRaw($start, $end, $granularity, 'ping_ms', $slugs),
            'latencySeries'     => $this->pivotRaw($start, $end, $granularity, 'ping_ms + IFNULL(jitter_ms, 0)', $slugs),
            'jitterSeries'      => $this->pivotRaw($start, $end, $granularity, 'jitter_ms', $slugs),
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
            'daily', 'weekly' => '%m/%d %H:%i',
            default           => '%H:%i',
        };
    }

    /**
     * @return array<int, array{slug: string, label: string}>
     */
    private function fetchActiveProviders(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $slugs = DB::table('speed_results')
            ->select('provider_slug')
            ->where('status', 'success')
            ->whereBetween('measured_at', [$start, $end])
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
        $rows = DB::table('speed_results')
            ->select([
                DB::raw("DATE_FORMAT(measured_at, '{$fmt}') as label"),
                DB::raw('measured_at'),
                'provider_slug',
                DB::raw('ROUND(download_mbps, 2) as dl'),
                DB::raw('ROUND(upload_mbps, 2) as ul'),
            ])
            ->where('status', 'success')
            ->whereBetween('measured_at', [$start, $end])
            ->whereIn('provider_slug', $providerSlugs)
            ->oldest('measured_at')
            ->get();

        /** @var array<string, array<string, float|string|null>> $buckets */
        $buckets = [];

        foreach ($rows as $row) {
            $key = (string) $row->measured_at;
            $slug = (string) $row->provider_slug;

            if (! isset($buckets[$key])) {
                $buckets[$key] = ['label' => (string) $row->label];
            }

            $buckets[$key]["{$slug}_dl"] = $row->dl !== null ? (float) $row->dl : null;
            $buckets[$key]["{$slug}_ul"] = $row->ul !== null ? (float) $row->ul : null;
        }

        return array_values($buckets);
    }

    /**
     * Individual results for a single metric expression, pivoted by provider.
     * No averaging — every row in speed_results becomes a data point.
     *
     * NOTE: $metric is a trusted internal SQL expression, never user-supplied.
     *
     * @param string[] $providerSlugs
     *
     * @return array<int, array<string, float|string|null>>
     */
    private function pivotRaw(
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $granularity,
        string $metric,
        array $providerSlugs,
    ): array {
        if ($providerSlugs === []) {
            return [];
        }

        $fmt = $this->labelFormat($granularity);
        $rows = DB::table('speed_results')
            ->select([
                DB::raw("DATE_FORMAT(measured_at, '{$fmt}') as label"),
                DB::raw('measured_at'),
                'provider_slug',
                DB::raw("ROUND({$metric}, 2) as value"),
            ])
            ->where('status', 'success')
            ->whereBetween('measured_at', [$start, $end])
            ->whereIn('provider_slug', $providerSlugs)
            ->oldest('measured_at')
            ->get();

        /** @var array<string, array<string, float|string|null>> $buckets */
        $buckets = [];

        foreach ($rows as $row) {
            $key = (string) $row->measured_at;

            if (! isset($buckets[$key])) {
                $buckets[$key] = ['label' => (string) $row->label];
            }

            $buckets[$key][(string) $row->provider_slug] = $row->value !== null
                ? (float) $row->value
                : null;
        }

        return array_values($buckets);
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    private function fetchActivePingTargets(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return DB::table('ping_targets')
            ->select(['ping_targets.id', 'ping_targets.label'])
            ->join('ping_results', 'ping_results.ping_target_id', '=', 'ping_targets.id')
            ->where('ping_targets.is_enabled', true)
            ->whereIn('ping_results.status', ['success', 'partial'])
            ->whereNotNull('ping_results.avg_ms')
            ->whereBetween('ping_results.measured_at', [$start, $end])
            ->distinct()
            ->orderBy('ping_targets.label')
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
        $rows = DB::table('ping_results')
            ->select([
                DB::raw("DATE_FORMAT(measured_at, '{$fmt}') as label"),
                DB::raw('measured_at'),
                'ping_target_id',
                DB::raw('ROUND(avg_ms, 2) as value'),
            ])
            ->whereIn('status', ['success', 'partial'])
            ->whereNotNull('avg_ms')
            ->whereBetween('measured_at', [$start, $end])
            ->whereIn('ping_target_id', $targetIds)
            ->oldest('measured_at')
            ->get();

        /** @var array<string, array<string, float|string|null>> $buckets */
        $buckets = [];

        foreach ($rows as $row) {
            $key = (string) $row->measured_at;

            if (! isset($buckets[$key])) {
                $buckets[$key] = ['label' => (string) $row->label];
            }

            $buckets[$key][(string) $row->ping_target_id] = $row->value !== null
                ? (float) $row->value
                : null;
        }

        return array_values($buckets);
    }
}
