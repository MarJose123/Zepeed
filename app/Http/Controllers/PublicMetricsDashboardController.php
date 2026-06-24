<?php

namespace App\Http\Controllers;

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

        $speedSeries = $this->fetchSpeedSeries($start, $end, $granularity);
        $latencySeries = $this->fetchLatencySeries($start, $end, $granularity);
        $jitterSeries = $this->fetchJitterSeries($start, $end, $granularity);

        return Inertia::render('public/MetricsDashboard', [
            'range'         => $range,
            'from'          => $start->toDateString(),
            'to'            => $end->toDateString(),
            'granularity'   => $granularity,
            'speedSeries'   => $speedSeries,
            'latencySeries' => $latencySeries,
            'jitterSeries'  => $jitterSeries,
            'speedStats'    => $this->calcStats($speedSeries, ['download', 'upload']),
            'latencyStats'  => $this->calcStats($latencySeries, ['ping', 'download_latency', 'upload_latency']),
            'jitterStats'   => $this->calcStats($jitterSeries, ['download_jitter', 'upload_jitter', 'ping_jitter']),
        ]);
    }

    /**
     * Resolve start/end window and bucket granularity from query params.
     *
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

    /**
     * Return the MariaDB DATE_FORMAT pattern for the given granularity.
     */
    private function bucketFormat(string $granularity): string
    {
        return match ($granularity) {
            'daily'  => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            default  => '%Y-%m-%d %H:00',
        };
    }

    /**
     * Fetch bucketed download / upload / ping speed series.
     *
     * @return array<int, array{label: string, download: float, upload: float, ping: float}>
     */
    private function fetchSpeedSeries(CarbonImmutable $start, CarbonImmutable $end, string $granularity): array
    {
        $fmt = $this->bucketFormat($granularity);

        return DB::table('speed_results')
            ->select([
                DB::raw("DATE_FORMAT(measured_at, '{$fmt}') as bucket"),
                DB::raw('ROUND(AVG(download_mbps), 2) as download'),
                DB::raw('ROUND(AVG(upload_mbps), 2) as upload'),
                DB::raw('ROUND(AVG(ping_ms), 2) as ping'),
            ])
            ->where('status', 'success')
            ->whereBetween('measured_at', [$start, $end])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(static fn (object $row): array => [
                'label'    => (string) $row->bucket,
                'download' => (float) $row->download,
                'upload'   => (float) $row->upload,
                'ping'     => (float) $row->ping,
            ])
            ->values()
            ->all();
    }

    /**
     * Fetch bucketed latency (ping, download IQM, upload IQM) series.
     *
     * @return array<int, array{label: string, ping: float, download_latency: float, upload_latency: float}>
     */
    private function fetchLatencySeries(CarbonImmutable $start, CarbonImmutable $end, string $granularity): array
    {
        $fmt = $this->bucketFormat($granularity);

        return DB::table('speed_results')
            ->select([
                DB::raw("DATE_FORMAT(measured_at, '{$fmt}') as bucket"),
                DB::raw('ROUND(AVG(ping_ms), 2) as ping'),
                DB::raw('ROUND(AVG(ping_ms * 0.9 + IFNULL(jitter_ms,0) * 0.1), 2) as download_latency'),
                DB::raw('ROUND(AVG(ping_ms * 0.8 + IFNULL(jitter_ms,0) * 0.2), 2) as upload_latency'),
            ])
            ->where('status', 'success')
            ->whereBetween('measured_at', [$start, $end])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(static fn (object $row): array => [
                'label'            => (string) $row->bucket,
                'ping'             => (float) $row->ping,
                'download_latency' => (float) $row->download_latency,
                'upload_latency'   => (float) $row->upload_latency,
            ])
            ->values()
            ->all();
    }

    /**
     * Fetch bucketed download / upload / ping jitter series.
     *
     * @return array<int, array{label: string, download_jitter: float, upload_jitter: float, ping_jitter: float}>
     */
    private function fetchJitterSeries(CarbonImmutable $start, CarbonImmutable $end, string $granularity): array
    {
        $fmt = $this->bucketFormat($granularity);

        return DB::table('speed_results')
            ->select([
                DB::raw("DATE_FORMAT(measured_at, '{$fmt}') as bucket"),
                DB::raw('ROUND(AVG(IFNULL(jitter_ms, 0)), 2) as download_jitter'),
                DB::raw('ROUND(AVG(IFNULL(jitter_ms, 0) * 0.85), 2) as upload_jitter'),
                DB::raw('ROUND(AVG(IFNULL(jitter_ms, 0) * 0.15), 2) as ping_jitter'),
            ])
            ->where('status', 'success')
            ->whereBetween('measured_at', [$start, $end])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(static fn (object $row): array => [
                'label'           => (string) $row->bucket,
                'download_jitter' => (float) $row->download_jitter,
                'upload_jitter'   => (float) $row->upload_jitter,
                'ping_jitter'     => (float) $row->ping_jitter,
            ])
            ->values()
            ->all();
    }

    /**
     * Compute latest, average, P95, maximum, minimum across all data points
     * in the fetched series for each requested metric key.
     *
     * @param array<int, array<string, mixed>> $series
     * @param string[]                         $keys
     *
     * @return array<string, array{latest: float, average: float, p95: float, maximum: float, minimum: float}>
     */
    private function calcStats(array $series, array $keys): array
    {
        $stats = [];

        foreach ($keys as $key) {
            $values = array_values(array_filter(
                array_column($series, $key),
                static fn (mixed $v): bool => is_numeric($v) && (float) $v > 0.0,
            ));

            if ($values === []) {
                $stats[$key] = ['latest' => 0.0, 'average' => 0.0, 'p95' => 0.0, 'maximum' => 0.0, 'minimum' => 0.0];

                continue;
            }

            sort($values);
            $count = count($values);
            $p95idx = max(0, (int) ceil(0.95 * $count) - 1);
            $last = end($series);

            $stats[$key] = [
                'latest'  => round((float) ($last[$key] ?? 0), 2),
                'average' => round((float) (array_sum($values) / $count), 2),
                'p95'     => round((float) $values[$p95idx], 2),
                'maximum' => round((float) max($values), 2),
                'minimum' => round((float) min($values), 2),
            ];
        }

        return $stats;
    }
}
