<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProviderScheduleResource;
use App\Models\ProviderSchedule;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Provider Schedule Endpoints
 *
 * Provides access to speedtest execution schedules configured for each provider.
 */
class ProviderScheduleController extends Controller
{
    /**
     * List provider schedules with pagination, filtering, and sorting.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     * @queryParam enabled boolean Filter by enabled status (0 or 1).
     * @queryParam sort array Sort by field: ?sort[created_at]=desc.
     */
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max((int) request()->query('per_page', 25), 1), 100);

        $schedules = ProviderSchedule::query()
            ->filterByQueryString()
            ->sortByQueryString()
            ->paginate($perPage)
            ->withQueryString();

        return ProviderScheduleResource::collection($schedules)->additional([
            'success' => filled($schedules),
            'code'    => 200,
        ]);
    }
}
