<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SpeedResultResource;
use App\Models\SpeedResult;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Speedtest Results Endpoints
 *
 * Provides access to historical speedtest measurements including download speed,
 * upload speed, latency, jitter, and provider information.
 */
class SpeedResultController extends Controller
{
    /**
     * List recent speedtest results.
     *
     * Retrieves the most recent speedtest measurements across all providers.
     * Returns up to 100 most recent results in reverse chronological order
     * (most recent first). Includes download/upload speeds, latency, jitter,
     * packet loss, provider details, and measurement timestamp.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": "550e8400-e29b-41d4-a716-446655440000",
     *       "provider_id": "550e8400-e29b-41d4-a716-446655440010",
     *       "provider_name": "Ookla",
     *       "download_mbps": 250.5,
     *       "upload_mbps": 50.2,
     *       "latency_ms": 15.3,
     *       "jitter_ms": 2.1,
     *       "packet_loss_percent": 0.0,
     *       "measured_at": "2024-01-15T10:30:00Z"
     *     }
     *   ]
     * }
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $results = SpeedResult::query()
            ->latest('measured_at')
            ->limit(100)
            ->get();

        return SpeedResultResource::collection($results);
    }
}
