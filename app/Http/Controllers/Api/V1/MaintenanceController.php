<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MaintenanceWindowResource;
use App\Models\MaintenanceWindow;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Maintenance Window Endpoints
 *
 * Provides information about scheduled maintenance windows.
 * Maintenance windows suppress alerts during planned downtime periods.
 */
class MaintenanceController extends Controller
{
    /**
     * List maintenance schedules with pagination, filtering, and sorting.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     * @queryParam starts_at_from string Filter by start date (Y-m-d format).
     * @queryParam starts_at_to string Filter by end date (Y-m-d format).
     * @queryParam is_active boolean Filter by active status (0 or 1).
     * @queryParam sort array Sort by field: ?sort[starts_at]=desc.
     */
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max((int) request()->query('per_page', 25), 1), 100);

        $windows = MaintenanceWindow::query()
            ->filterByQueryString()
            ->sortByQueryString()
            ->paginate($perPage)
            ->withQueryString();

        return MaintenanceWindowResource::collection($windows)->additional([
            'success' => filled($windows),
            'code'    => 200,
        ]);
    }
}
