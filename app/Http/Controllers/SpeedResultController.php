<?php

namespace App\Http\Controllers;

use App\Http\Requests\SpeedResultIndexRequest;
use App\Http\Resources\SpeedResultResource;
use App\Models\Provider;
use App\Models\SpeedResult;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final class SpeedResultController extends Controller
{
    public function download(SpeedResultIndexRequest $request): Response
    {
        [$results, $providers, $months, $filters, $stats] = $this->resolve($request, 'download');

        return Inertia::render('results/Download', [
            'results'   => $results,
            'providers' => $providers,
            'months'    => $months,
            'filters'   => $filters,
            'stats'     => $stats,
        ]);
    }

    public function upload(SpeedResultIndexRequest $request): Response
    {
        [$results, $providers, $months, $filters, $stats] = $this->resolve($request, 'upload');

        return Inertia::render('results/Upload', [
            'results'   => $results,
            'providers' => $providers,
            'months'    => $months,
            'filters'   => $filters,
            'stats'     => $stats,
        ]);
    }

    public function ping(SpeedResultIndexRequest $request): Response
    {
        [$results, $providers, $months, $filters, $stats] = $this->resolve($request, 'ping');

        return Inertia::render('results/Latency', [
            'results'   => $results,
            'providers' => $providers,
            'months'    => $months,
            'filters'   => $filters,
            'stats'     => $stats,
        ]);
    }

    /**
     * @return array{0: mixed, 1: Collection, 2: Collection, 3: array<string, mixed>, 4: array<string, mixed>}
     */
    private function resolve(SpeedResultIndexRequest $request, string $metric): array
    {
        $validated = $request->validated();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $provider = $validated['provider'] ?? null;
        $month = $validated['month'] ?? null;

        $baseQuery = SpeedResult::query()
            ->select('speed_results.*', 'providers.name as provider_name')
            ->leftJoin('providers', 'providers.slug', '=', 'speed_results.provider_slug')
            ->where('speed_results.status', 'success')
            ->when($provider, fn ($q) => $q->where('speed_results.provider_slug', $provider))
            ->when($month, function ($q) use ($month): void {
                [$year, $mon] = explode('-', (string) $month);
                $q->whereYear('speed_results.measured_at', $year)
                    ->whereMonth('speed_results.measured_at', $mon);
            });

        $results = (clone $baseQuery)
            ->latest('measured_at')
            ->paginate($perPage)
            ->withQueryString();

        $providers = Provider::query()
            ->select(['slug', 'name'])
            ->orderBy('name')
            ->get();

        $months = SpeedResult::query()
            ->selectRaw("DATE_FORMAT(measured_at, '%Y-%m') as month")
            ->where('status', 'success')
            ->groupByRaw("DATE_FORMAT(measured_at, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(measured_at, '%Y-%m') DESC")
            ->pluck('month');

        $stats = $this->buildStats((clone $baseQuery)->get(), $metric);

        $filters = [
            'provider' => $provider,
            'month'    => $month,
            'per_page' => $perPage,
        ];

        return [SpeedResultResource::collection($results), $providers, $months, $filters, $stats];
    }

    /**
     * @param Collection<int, SpeedResult> $rows
     *
     * @return array<string, mixed>
     */
    private function buildStats(Collection $rows, string $metric): array
    {
        $column = match ($metric) {
            'download' => 'download_mbps',
            'upload'   => 'upload_mbps',
            'ping'     => 'ping_ms',
            default    => 'download_mbps',
        };

        if ($rows->isEmpty()) {
            return [
                'average'         => null,
                'peak'            => null,
                'best'            => null,
                'worst'           => null,
                'lowest'          => null,
                'threshold_count' => 0,
                'threshold_label' => '',
                'threshold_pct'   => 0,
                'total'           => 0,
            ];
        }

        $values = $rows->pluck($column)
            ->filter(fn ($v) => $v !== null)
            ->map(fn ($v) => (float) $v);

        $total = $values->count();

        $thresholdCount = match ($metric) {
            'download' => $values->filter(fn ($v) => $v < 25)->count(),
            'upload'   => $values->filter(fn ($v) => $v < 10)->count(),
            'ping'     => $values->filter(fn ($v) => $v > 60)->count(),
            default    => 0,
        };

        $thresholdLabel = match ($metric) {
            'download' => 'Below 25 Mbps',
            'upload'   => 'Below 10 Mbps',
            'ping'     => 'Above 60 ms',
            default    => '',
        };

        return [
            'average'         => round($values->avg(), 2),
            'peak'            => $metric !== 'ping' ? round($values->max(), 2) : null,
            'best'            => $metric === 'ping' ? round($values->min(), 2) : null,
            'worst'           => $metric === 'ping' ? round($values->max(), 2) : null,
            'lowest'          => $metric !== 'ping' ? round($values->min(), 2) : null,
            'threshold_count' => $thresholdCount,
            'threshold_label' => $thresholdLabel,
            'threshold_pct'   => $total > 0 ? round(($thresholdCount / $total) * 100, 1) : 0,
            'total'           => $total,
        ];
    }
}
