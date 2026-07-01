<?php

namespace App\Http\Controllers;

use App\Http\Requests\PingResultIndexRequest;
use App\Http\Resources\PingResultResource;
use App\Http\Resources\PingTargetResource;
use App\Models\PingResult;
use App\Models\PingTarget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class PingResultController extends Controller
{
    public function index(PingResultIndexRequest $request): Response
    {
        $validated = $request->validated();

        $range = $validated['range'] ?? '24h';
        $targetId = $validated['target'] ?? null;
        $status = $validated['status'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 25);

        $baseQuery = PingResult::query()
            ->with('target')
            ->inDateRange($range)
            ->when($targetId, static fn ($q) => $q->where('ping_target_id', $targetId))
            ->when($status, static fn ($q) => $q->where('status', $status));

        $results = (clone $baseQuery)
            ->latest('measured_at')
            ->paginate($perPage)
            ->withQueryString();

        $allInRange = (clone $baseQuery)->get();

        $stats = [
            'total_tests'     => $allInRange->count(),
            'avg_latency_ms'  => $allInRange->whereNotNull('avg_ms')->avg('avg_ms'),
            'avg_packet_loss' => $allInRange->avg('packet_loss_percent'),
        ];

        $targets = PingTarget::query()->orderBy('label')->get();

        return Inertia::render('network/PingResults', [
            'results'    => PingResultResource::collection($results)->resolve(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
                'from'         => $results->firstItem(),
                'to'           => $results->lastItem(),
            ],
            'stats'   => $stats,
            'trend'   => $this->buildTrend($targets, $targetId, $range),
            'targets' => PingTargetResource::collection($targets)->resolve(),
            'filters' => [
                'range'    => $range,
                'target'   => $targetId,
                'status'   => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Build per-target trend data for multi-line charts.
     *
     * Returns an array of time buckets. Each bucket has a `label` (HH:MM or
     * date string) plus one key per target id containing avg_ms and
     * packet_loss values, e.g.:
     *
     * [
     *   { label: '08:00', 'uuid-1': ['avg_ms' => 21.3, 'loss' => 0.0], ... },
     *   ...
     * ]
     *
     * @param Collection<int, PingTarget> $targets
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildTrend(
        Collection $targets,
        ?string $filterTargetId,
        string $range,
    ): array {
        $hours = match ($range) {
            '7d'    => 168,
            '30d'   => 720,
            default => 24,
        };

        $format = match ($range) {
            '7d', '30d' => 'Y-m-d H:00:00',
            default     => 'Y-m-d H:i:00',
        };

        $activeTargets = $filterTargetId
            ? $targets->where('id', $filterTargetId)
            : $targets;

        if ($activeTargets->isEmpty()) {
            return [];
        }

        $rows = PingResult::query()
            ->where('measured_at', '>=', now()->subHours($hours))
            ->whereIn('ping_target_id', $activeTargets->pluck('id')->all())
            ->toBase()
            ->select(['ping_target_id', 'measured_at', 'avg_ms', 'packet_loss_percent'])
            ->get();

        /** @var array<string, array<string, mixed>> $bucketMap */
        $bucketMap = [];

        $grouped = $rows->groupBy(fn (object $row): string => $row->ping_target_id . '|' . Date::parse($row->measured_at)->format($format));

        foreach ($grouped as $group) {
            $first = $group->first();
            $bucket = Date::parse($first->measured_at)->format($format);
            $targetId = (string) $first->ping_target_id;

            $bucketMap[$bucket] ??= [];
            $bucketMap[$bucket][$targetId] = [
                'avg_ms' => ($avg = $group->avg('avg_ms')) !== null ? round((float) $avg, 2) : null,
                'loss'   => ($loss = $group->avg('packet_loss_percent')) !== null ? round((float) $loss, 2) : null,
            ];
        }

        ksort($bucketMap);

        return array_values(array_map(
            static function (string $bucket, array $targetData): array {
                // Derive a short display label from the bucket string
                $label = strlen($bucket) >= 16
                    ? substr($bucket, 11, 5)   // HH:MM for minute buckets
                    : substr($bucket, 5, 5);   // MM-DD for day buckets

                return array_merge(['label' => $label], $targetData);
            },
            array_keys($bucketMap),
            array_values($bucketMap),
        ));
    }
}
