<?php

namespace App\Http\Controllers;

use App\Concerns\TranslatesDateFormat;
use App\Enums\SpeedtestServer;
use App\Models\PingResult;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Models\SpeedResult;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use TranslatesDateFormat;

    public function index(Request $request): Response
    {
        $range = (string) $request->query('range', '1d');
        $from = $request->query('from');
        $to = $request->query('to');

        [$start, $end] = $this->resolveRange($range, $from, $to);

        $providers = Provider::query()->get();
        $chartProviders = $this->fetchActiveChartProviders($start, $end);
        $slugs = array_column($chartProviders, 'slug');

        return Inertia::render('Dashboard', [
            'providerCards'     => $this->buildProviderCards($providers),
            'chartSeries'       => $this->fetchSpeedSeries($start, $end, $slugs),
            'chartAverages'     => $this->buildChartAverages($start, $end, $slugs),
            'chartProviders'    => $chartProviders,
            'range'             => $range,
            'from'              => $start->toDateString(),
            'to'                => $end->toDateString(),
            'recentPingResults' => self::buildRecentPingResults(),
        ]);
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function resolveRange(string $range, mixed $from, mixed $to): array
    {
        if ($range === 'custom' && $from && $to) {
            return [
                CarbonImmutable::parse((string) $from)->startOfDay(),
                CarbonImmutable::parse((string) $to)->endOfDay(),
            ];
        }

        return match ($range) {
            '7d'    => [CarbonImmutable::now()->subDays(7),  CarbonImmutable::now()],
            '30d'   => [CarbonImmutable::now()->subDays(30), CarbonImmutable::now()],
            default => [CarbonImmutable::now()->subDay(),    CarbonImmutable::now()],
        };
    }

    /**
     * XAxis label format — time only for ≤2-day ranges, date for wider ranges.
     */
    private function labelFormat(CarbonImmutable $start, CarbonImmutable $end): string
    {
        $days = (int) $start->diffInDays($end);

        if ($days <= 2) {
            return '%H:%i';
        }

        if ($start->year !== $end->year || $days > 365) {
            return '%m/%Y';
        }

        return '%m/%d';
    }

    /**
     * Providers that have at least one successful result in the date window.
     *
     * @return array<int, array{slug: string, label: string}>
     */
    private function fetchActiveChartProviders(CarbonImmutable $start, CarbonImmutable $end): array
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
     * Pivoted speed series: each successful result becomes a row.
     * Keys: "{slug}_dl", "{slug}_ul", plus "label" for the XAxis tick.
     *
     * @param string[] $slugs
     *
     * @return array<int, array<string, float|string|null>>
     */
    private function fetchSpeedSeries(CarbonImmutable $start, CarbonImmutable $end, array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $fmt = $this->labelFormat($start, $end);

        $rows = SpeedResult::query()
            ->where('status', 'success')
            ->whereBetween('measured_at', [$start, $end])
            ->whereIn('provider_slug', $slugs)
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
                $buckets[$key] = ['label' => $this->formatDate(Date::parse($row->measured_at), $fmt)];
            }

            $buckets[$key]["{$slug}_dl"] = $row->download_mbps !== null ? round((float) $row->download_mbps, 2) : null;
            $buckets[$key]["{$slug}_ul"] = $row->upload_mbps !== null ? round((float) $row->upload_mbps, 2) : null;
        }

        return array_values($buckets);
    }

    /**
     * Per-series average for the reference line: "{slug}_dl" and "{slug}_ul" keys.
     *
     * @param string[] $slugs
     *
     * @return array<string, float>
     */
    private function buildChartAverages(CarbonImmutable $start, CarbonImmutable $end, array $slugs): array
    {
        $averages = [];

        foreach ($slugs as $slug) {
            $rows = SpeedResult::query()
                ->where('status', 'success')
                ->where('provider_slug', $slug)
                ->whereBetween('measured_at', [$start, $end])
                ->toBase()
                ->get(['download_mbps', 'upload_mbps']);

            $averages["{$slug}_dl"] = round((float) $rows->avg('download_mbps'), 2);
            $averages["{$slug}_ul"] = round((float) $rows->avg('upload_mbps'), 2);
        }

        return $averages;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildRecentPingResults(): array
    {
        return PingResult::query()
            ->with('target')
            ->latest('measured_at')
            ->limit(10)
            ->get()
            ->map(static fn (PingResult $r): array => [
                'id'                  => $r->id,
                'target_label'        => $r->target->label,
                'target_host'         => $r->target->host,
                'status'              => $r->status->value,
                'avg_ms'              => $r->avg_ms,
                'packet_loss_percent' => $r->packet_loss_percent,
                'measured_at'         => $r->measured_at->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, Provider> $providers
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildProviderCards(Collection $providers): array
    {
        return $providers->map(function (Provider $provider): array {
            $latest = SpeedResult::query()
                ->forProvider($provider->slug)
                ->successful()
                ->latest('measured_at')
                ->first();

            $schedule = ProviderSchedule::query()
                ->where('provider_slug', $provider->slug->value)
                ->first();

            return [
                'slug'            => $provider->slug->value,
                'name'            => $provider->slug->label(),
                'website_link'    => $provider->slug->websiteLink(),
                'status_badge'    => $provider->status_badge,
                'last_run_at'     => $provider->last_run_at?->toIso8601String(),
                'last_run_status' => $provider->last_run_status,
                'is_enabled'      => $provider->is_enabled,
                'next_run_at'     => $schedule?->nextRunAt()?->toIso8601String(),
                'latest'          => $latest ? [
                    'download_mbps' => (float) $latest->download_mbps,
                    'upload_mbps'   => (float) $latest->upload_mbps,
                    'ping_ms'       => (float) $latest->ping_ms,
                    'jitter_ms'     => $latest->jitter_ms !== null ? (float) $latest->jitter_ms : null,
                ] : null,
                'sparkline' => $this->buildSparkline($provider->slug),
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSparkline(SpeedtestServer $slug): array
    {
        return SpeedResult::query()
            ->forProvider($slug)
            ->successful()
            ->latest('measured_at')
            ->limit(12)
            ->get()
            ->reverse()
            ->map(static function (SpeedResult $r): array {
                /** @var Carbon $measuredAt */
                $measuredAt = $r->measured_at;

                return [
                    'download_mbps' => (float) $r->download_mbps,
                    'upload_mbps'   => (float) $r->upload_mbps,
                    'measured_at'   => $measuredAt->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }
}
