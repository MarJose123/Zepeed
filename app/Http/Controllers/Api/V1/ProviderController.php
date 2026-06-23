<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;

/**
 * Provider Management Endpoints
 *
 * Provides access to speedtest provider information and their current status.
 * Providers are the various speedtest services (Ookla, LibreSpeed, Cloudflare, etc.)
 * configured in the system.
 */
class ProviderController extends Controller
{
    /**
     * List all configured providers with pagination, filtering, and sorting.
     *
     * Retrieves all speedtest providers available in the system with support for
     * pagination, filtering by enabled status, and sorting.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     * @queryParam enabled boolean Filter by enabled status (0 or 1).
     * @queryParam sort array Sort by field: ?sort[name]=asc or ?sort[created_at]=desc.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->query('per_page', 25), 100);
        $perPage = max($perPage, 1);

        $providers = Provider::query()
            ->filterByQueryString()
            ->sortByQueryString()
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated($providers);
    }
}
