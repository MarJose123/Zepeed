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
     * List speedtest results with pagination, filtering, sorting, and search.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     * @queryParam provider_slug string Filter by provider slug (ookla, librespeed, netflix, cloudflare).
     * @queryParam measured_at_from string Filter by start date (Y-m-d format).
     * @queryParam measured_at_to string Filter by end date (Y-m-d format).
     * @queryParam sort array Sort by field: ?sort[measured_at]=desc or ?sort[download_mbps]=asc.
     * @queryParam search string Full-text search across server_name, server_location, isp.
     */
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max((int) request()->query('per_page', 25), 1), 100);

        $results = SpeedResult::query()
            ->filterByQueryString()
            ->sortByQueryString()
            ->searchByQueryString()
            ->paginate($perPage)
            ->withQueryString();

        return SpeedResultResource::collection($results)->additional([
            'success' => filled($results),
            'code'    => 200,
        ]);
    }
}
