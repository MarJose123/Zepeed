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
     * List ping results with pagination, filtering, sorting, and search.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     * @queryParam target_id string Filter by ping target UUID.
     * @queryParam status string Filter by status (success, partial, failed).
     * @queryParam measured_at_from string Filter by start date (Y-m-d format).
     * @queryParam measured_at_to string Filter by end date (Y-m-d format).
     * @queryParam sort array Sort by field: ?sort[measured_at]=desc or ?sort[avg_ms]=asc.
     */
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max((int) request()->query('per_page', 25), 1), 100);

        $pings = PingResult::query()
            ->with('target')
            ->filterByQueryString()
            ->sortByQueryString()
            ->paginate($perPage)
            ->withQueryString();

        return PingResultResource::collection($pings)->additional([
            'success' => filled($pings),
            'code'    => 200,
        ]);
    }
}
