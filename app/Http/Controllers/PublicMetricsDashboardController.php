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

        return Inertia::render('public/MetricsDashboard', [
            'range'          => $range,
            'from'           => $start->toDateString(),
            'to'             => $end->toDateString(),
            'granularity'    => $granularity,
            'providers'      => $providers,
            'downloadSeries' => $this->pivotMetric($start, $end, $granularity, 'download_mbps', $slugs),
            'uploadSeries'   => $this->pivotMetric($start, $end, $granularity, 'upload_mbps', $slugs),
            'pingSeries'     => $this->pivotMetric($start, $end, $granularity, 'ping_ms', $slugs),
            'latencySeries'  => $this->pivotMetric($start, $end, $granularity, 'ping_ms + IFNULL(jitter_ms, 0)', $slugs),
            'jitterSeries'   => $this->pivotMetric($start, $end, $granularity, 'jitter_ms', $slugs),
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

            $granularity = match (true) {
                $days <= 2  => 'hourly',
                $days <= 31 => 'daily',
                default     => 'weekly',
            };

            return [$start, $end, $granularity];
        }

        return match ($range) {
            '7d'    => [CarbonImmutable::now()->subDays(7),  CarbonImmutable::now(), 'daily'],
            '30d'   => [CarbonImmutable::now()->subDays(30), CarbonImmutable::now(), 'daily'],
            default => [CarbonImmutable::now()->subDay(),    CarbonImmutable::now(), 'hourly'],
        };
    }

    private function bucketFormat(string $granularity): string
    {
        return match ($granularity) {
            'daily'  => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            default  => '%Y-%m-%d %H:00',
        };
    }

    /**
     * Return providers that have at least one successful result in the range.
     *
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
     * Pivot a single aggregated metric by provider into flat time-bucket rows.
     * Each returned row: { label: bucket, <provider_slug>: float|null, ... }
     *
     * NOTE: $metric is an internal SQL expression — never user-supplied.
     *
     * @param string[] $providerSlugs
     *
     * @return array<int, array<string, float|string|null>>
     */
    private function pivotMetric(
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $granularity,
        string $metric,
        array $providerSlugs,
    ): array {
        if ($providerSlugs === []) {
            return [];
        }

        $fmt = $this->bucketFormat($granularity);
        $rows = DB::table('speed_results')
            ->select([
                DB::raw("DATE_FORMAT(measured_at, '{$fmt}') as bucket"),
                'provider_slug',
                DB::raw("ROUND(AVG({$metric}), 2) as value"),
            ])
            ->where('status', 'success')
            ->whereBetween('measured_at', [$start, $end])
            ->whereIn('provider_slug', $providerSlugs)
            ->groupBy('bucket', 'provider_slug')
            ->orderBy('bucket')
            ->get();

        /** @var array<string, array<string, float|string|null>> $buckets */
        $buckets = [];

        foreach ($rows as $row) {
            $bucket = (string) $row->bucket;

            if (! isset($buckets[$bucket])) {
                $buckets[$bucket] = ['label' => $bucket];
            }

            $buckets[$bucket][(string) $row->provider_slug] = $row->value !== null
                ? (float) $row->value
                : null;
        }

        return array_values($buckets);
    }
}
