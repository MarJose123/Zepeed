<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\PingResult;
use Illuminate\Http\JsonResponse;

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
     * Retrieves ping measurements with support for pagination, filtering by target
     * and status, date range filtering, sorting, and full-text search.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     * @queryParam target_id string Filter by target UUID.
     * @queryParam status string Filter by status (success, failed).
     * @queryParam measured_at_from string Filter by start date (Y-m-d format).
     * @queryParam measured_at_to string Filter by end date (Y-m-d format).
     * @queryParam sort array Sort by field: ?sort[measured_at]=desc or ?sort[latency_ms]=asc.
     * @queryParam search string Full-text search across target host and name.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->query('per_page', 25), 100);
        $perPage = max($perPage, 1);

        $pings = PingResult::query()
            ->with('target')
            ->filterByQueryString()
            ->sortByQueryString()
            ->searchByQueryString()
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated($pings);
    }
}
