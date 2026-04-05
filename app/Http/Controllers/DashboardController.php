<?php

namespace App\Http\Controllers;

use App\Enums\SpeedtestServer;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Models\SpeedResult;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $providers = Provider::query()->get();

        //        dd($this->buildProviderCards($providers));

        return Inertia::render('Dashboard', [
            'providerCards' => $this->buildProviderCards($providers),
            'chartData'     => $this->buildChartData(),
        ]);
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
            ->map(function (SpeedResult $r): array {
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

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildChartData(): array
    {
        return [
            '24h' => $this->buildRangeData(hours: 24, groupFormat: 'Y-m-d H:i', labelFormat: 'H:i'),
            '7d'  => $this->buildRangeData(hours: 168, groupFormat: 'Y-m-d', labelFormat: 'D d'),
            '30d' => $this->buildRangeData(hours: 720, groupFormat: 'Y-m-d', labelFormat: 'M d'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRangeData(int $hours, string $groupFormat, string $labelFormat): array
    {
        $since = now()->subHours($hours);

        /** @var Collection<int, SpeedResult> $results */
        $results = SpeedResult::query()
            ->successful()
            ->where('measured_at', '>=', $since)
            ->oldest('measured_at')
            ->get();

        $grouped = [];

        foreach (SpeedtestServer::cases() as $server) {
            /** @var Collection<int, SpeedResult> $providerResults */
            $providerResults = $results->filter(
                fn (SpeedResult $r) => $r->provider_slug === $server
            );

            $grouped[$server->value] = $providerResults
                ->groupBy(function (SpeedResult $r) use ($groupFormat): string {
                    /** @var Carbon $measuredAt */
                    $measuredAt = $r->measured_at;

                    return $measuredAt->format($groupFormat);
                })
                ->map(function (Collection $group): array {
                    /** @var Collection<int, SpeedResult> $group */
                    return [
                        'download_mbps' => round(
                            $group->avg(fn (SpeedResult $r) => (float) $r->download_mbps) ?? 0,
                            2
                        ),
                        'upload_mbps' => round(
                            $group->avg(fn (SpeedResult $r) => (float) $r->upload_mbps) ?? 0,
                            2
                        ),
                    ];
                })
                ->all();
        }

        // Collect and sort all unique time bucket keys
        $rawKeys = collect($grouped)
            ->flatMap(fn (array $data) => array_keys($data))
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Convert raw keys to human-readable labels
        $labels = array_map(function (string $key) use ($groupFormat, $labelFormat): string {
            $date = Date::createFromFormat($groupFormat, $key);

            return $date ? $date->format($labelFormat) : $key;
        }, $rawKeys);

        // Build per-provider download/upload arrays aligned to rawKeys
        $datasets = [];
        foreach (SpeedtestServer::cases() as $server) {
            $providerData = $grouped[$server->value];
            $datasets[$server->value] = [
                'download' => array_map(
                    fn (string $key) => $providerData[$key]['download_mbps'] ?? 0,
                    $rawKeys
                ),
                'upload' => array_map(
                    fn (string $key) => $providerData[$key]['upload_mbps'] ?? 0,
                    $rawKeys
                ),
            ];
        }

        return [
            'labels'   => array_values($labels),
            'datasets' => $datasets,
        ];
    }
}
