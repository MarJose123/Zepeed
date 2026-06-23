<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\SpeedResult;
use Illuminate\Http\JsonResponse;

/**
 * Speedtest Results Endpoints
 *
 * Provides access to historical speedtest measurements including download speed,
 * upload speed, latency, jitter, and provider information.
 */
class SpeedResultController extends Controller
{
    /**
     * List speedtest results with pagination, filtering, sorting, and search.
     *
     * Retrieves speedtest measurements with support for pagination, filtering by provider
     * and date range, sorting by various metrics, and full-text search.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     * @queryParam provider_slug string Filter by provider (ookla, librespeed, netflix, cloudflare).
     * @queryParam measured_at_from string Filter by start date (Y-m-d format).
     * @queryParam measured_at_to string Filter by end date (Y-m-d format).
     * @queryParam sort array Sort by field: ?sort[measured_at]=desc or ?sort[download_mbps]=asc.
     * @queryParam search string Full-text search across server_name, server_location, isp.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->query('per_page', 25), 100);
        $perPage = max($perPage, 1);

        $results = SpeedResult::query()
            ->filterByQueryString()
            ->sortByQueryString()
            ->searchByQueryString()
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated($results);
    }
}
