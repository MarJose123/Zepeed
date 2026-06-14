<?php

namespace App\Http\Controllers;

use App\Http\Requests\PingResultIndexRequest;
use App\Http\Resources\PingResultResource;
use App\Http\Resources\PingTargetResource;
use App\Models\PingResult;
use App\Models\PingTarget;
use Illuminate\Support\Facades\DB;
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
            'stats'      => $stats,
            'trend'      => self::buildTrend($targetId, $range),
            'targets'    => PingTargetResource::collection(
                PingTarget::query()->orderBy('label')->get()
            )->resolve(),
            'filters'    => [
                'range'    => $range,
                'target'   => $targetId,
                'status'   => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Build trend data for charts — minute buckets for 24h, hourly for 7d/30d.
     *
     * @return array<int, array{bucket: string, avg_ms: float|null, packet_loss: float|null}>
     */
    private function buildTrend(?string $targetId, string $range): array
    {
        $hours = match ($range) {
            '7d'    => 168,
            '30d'   => 720,
            default => 24,
        };

        $format = match ($range) {
            '7d', '30d' => '%Y-%m-%d %H:00:00',
            default     => '%Y-%m-%d %H:%i:00',
        };

        /** @var array<int, object{bucket: string, avg_ms: string|null, packet_loss: string|null}> $rows */
        $rows = DB::table('ping_results')
            ->selectRaw("DATE_FORMAT(measured_at, '{$format}') as bucket")
            ->selectRaw('AVG(avg_ms) as avg_ms')
            ->selectRaw('AVG(packet_loss_percent) as packet_loss')
            ->where('measured_at', '>=', now()->subHours($hours))
            ->when($targetId, static fn ($q) => $q->where('ping_target_id', $targetId))
            ->groupByRaw("DATE_FORMAT(measured_at, '{$format}')")
            ->orderBy('bucket')
            ->get()
            ->all();

        return array_map(static function (object $row): array {
            return [
                'bucket'      => (string) $row->bucket,
                'avg_ms'      => $row->avg_ms !== null ? round((float) $row->avg_ms, 2) : null,
                'packet_loss' => $row->packet_loss !== null ? round((float) $row->packet_loss, 2) : null,
            ];
        }, $rows);
    }
}
