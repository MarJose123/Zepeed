<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PingResultResource;
use App\Models\PingResult;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Ping Monitoring Endpoints
 *
 * Provides access to network connectivity monitoring results.
 * Tracks latency and reachability to configured network targets.
 */
class PingResultController extends Controller
{
    /**
     * List recent ping monitoring results.
     *
     * Retrieves the most recent ping measurements to all configured targets.
     * Returns up to 100 most recent results in reverse chronological order.
     * Each result includes the target host, latency, packet loss percentage,
     * response status, and measurement timestamp. Includes related target information.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": "550e8400-e29b-41d4-a716-446655440000",
     *       "target_id": "550e8400-e29b-41d4-a716-446655440020",
     *       "target": {
     *         "id": "550e8400-e29b-41d4-a716-446655440020",
     *         "host": "8.8.8.8",
     *         "name": "Google DNS"
     *       },
     *       "latency_ms": 12.5,
     *       "packet_loss_percent": 0.0,
     *       "status": "success",
     *       "measured_at": "2024-01-15T10:30:00Z"
     *     }
     *   ]
     * }
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $pings = PingResult::query()
            ->with('target')
            ->latest('measured_at')
            ->limit(100)
            ->get();

        return PingResultResource::collection($pings);
    }
}
