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
     * List all maintenance windows.
     *
     * Retrieves all scheduled maintenance windows in reverse chronological order
     * (most recent first). Includes window start/end times, description, and
     * whether it affects all monitoring or specific providers. Useful for
     * clients to understand when the system expects reduced availability.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": "550e8400-e29b-41d4-a716-446655440000",
     *       "title": "Network Maintenance",
     *       "description": "ISP maintenance window",
     *       "starts_at": "2024-01-20T02:00:00Z",
     *       "ends_at": "2024-01-20T04:00:00Z",
     *       "is_global": true,
     *       "created_at": "2024-01-15T12:00:00Z"
     *     }
     *   ]
     * }
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $windows = MaintenanceWindow::query()
            ->latest('starts_at')
            ->get();

        return MaintenanceWindowResource::collection($windows);
    }
}
