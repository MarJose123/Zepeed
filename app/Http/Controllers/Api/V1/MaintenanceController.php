<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\MaintenanceWindow;
use Illuminate\Http\JsonResponse;

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
     * Retrieves all scheduled maintenance windows with support for pagination,
     * filtering by date range and active status, and sorting.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     * @queryParam starts_at_from string Filter by start date (Y-m-d format).
     * @queryParam starts_at_to string Filter by end date (Y-m-d format).
     * @queryParam is_active boolean Filter by active status (0 or 1).
     * @queryParam sort array Sort by field: ?sort[starts_at]=desc.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $perPage = min((int) request()->query('per_page', 25), 100);
        $perPage = max($perPage, 1);

        $windows = MaintenanceWindow::query()
            ->filterByQueryString()
            ->sortByQueryString()
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::paginated($windows);
    }
}
