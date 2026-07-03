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
    /**
     * Columns permitted for sorting, prefixed against the base table to
     * avoid ambiguity with the joined `providers` table.
     *
     * @var list<string>
     */
    private const array SORTABLE_COLUMNS = ['measured_at', 'download_mbps', 'upload_mbps', 'ping_ms', 'jitter_ms'];

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
        $sort = $validated['sort'] ?? null;
        $direction = $validated['direction'] ?? 'desc';
        $date = $validated['date'] ?? null;
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        $baseQuery = SpeedResult::query()
            ->select('speed_results.*', 'providers.name as provider_name')
            ->leftJoin('providers', 'providers.slug', '=', 'speed_results.provider_slug')
            ->where('speed_results.status', 'success')
            ->when($provider, static fn ($q) => $q->where('speed_results.provider_slug', $provider))
            ->when($date, static fn ($q) => $q->whereDate('speed_results.measured_at', $date))
            ->when($dateFrom && $dateTo, static fn ($q) => $q->whereBetween('speed_results.measured_at', [
                "{$dateFrom} 00:00:00", "{$dateTo} 23:59:59",
            ]))
            ->when($dateFrom && ! $dateTo, static fn ($q) => $q->where('speed_results.measured_at', '>=', "{$dateFrom} 00:00:00"))
            ->when($dateTo && ! $dateFrom, static fn ($q) => $q->where('speed_results.measured_at', '<=', "{$dateTo} 23:59:59"));

        $results = (clone $baseQuery)
            ->when(
                $sort !== null && in_array($sort, self::SORTABLE_COLUMNS, true),
                static fn ($q) => $q->orderBy("speed_results.{$sort}", $direction),
                static fn ($q) => $q->latest('speed_results.measured_at'),
            )
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

        $stats = self::buildStats((clone $baseQuery)->get(), $metric);

        $filters = [
            'provider'  => $provider,
            'per_page'  => $perPage,
            'sort'      => $sort,
            'direction' => $sort !== null ? $direction : null,
            'date'      => $date,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ];

        return [SpeedResultResource::collection($results), $providers, $months, $filters, $stats];
    }

    /**
     * @param Collection<int, SpeedResult> $rows
     *
     * @return array<string, mixed>
     */
    private static function buildStats(Collection $rows, string $metric): array
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
            ->filter(static fn ($v) => $v !== null)
            ->map(static fn ($v) => (float) $v);

        $total = $values->count();

        $thresholdCount = match ($metric) {
            'download' => $values->filter(static fn ($v) => $v < 25)->count(),
            'upload'   => $values->filter(static fn ($v) => $v < 10)->count(),
            'ping'     => $values->filter(static fn ($v) => $v > 60)->count(),
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
