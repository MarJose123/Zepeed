<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MaintenanceWindowType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceWindowRequest;
use App\Http\Requests\UpdateMaintenanceWindowRequest;
use App\Http\Resources\Api\MaintenanceWindowResource;
use App\Models\MaintenanceWindow;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

#[Group(
    name: 'Maintenance',
    description: 'Manage scheduled maintenance windows that suppress speedtest runs and alerts during planned downtime periods.',
    weight: 4,
)]
/**
 * Maintenance Window Endpoints
 *
 * Manage scheduled maintenance windows that suppress speedtest runs and
 * alerts during planned downtime periods.
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
    #[Endpoint(title: 'List maintenance windows', description: 'List maintenance schedules with pagination, filtering, and sorting.')]
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

    /**
     * Create a maintenance window.
     *
     * One-time windows require `starts_at`/`ends_at` in the future and must
     * not overlap an existing active window for the same provider(s).
     * Recurring windows require a `cron_expression` and `duration_minutes`.
     *
     * @bodyParam label string required Human-readable label.
     * @bodyParam type string required one_time|recurring|indefinite.
     * @bodyParam providers string[] required Provider slugs or "all".
     * @bodyParam starts_at string required for one_time. Must be in the future.
     * @bodyParam ends_at string required for one_time. Must be after starts_at.
     * @bodyParam cron_expression string required for recurring. Valid cron expression.
     * @bodyParam duration_minutes int required for recurring. 1-1440.
     * @bodyParam is_active boolean Default: true.
     * @bodyParam notes string nullable Max: 500.
     *
     * @param StoreMaintenanceWindowRequest $request
     */
    #[Endpoint(title: 'Create maintenance window', description: 'Create a maintenance window. One-time windows require future starts_at/ends_at without overlaps; recurring windows require a cron_expression and duration_minutes.')]
    public function store(StoreMaintenanceWindowRequest $request): JsonResponse
    {
        /** @var MaintenanceWindow $window */
        $window = MaintenanceWindow::query()->create($request->validated());

        return MaintenanceWindowResource::make($window->refresh())
            ->additional([
                'success' => true,
                'code'    => 201,
                'message' => 'Maintenance window created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a maintenance window.
     *
     * @param UpdateMaintenanceWindowRequest $request
     * @param MaintenanceWindow              $maintenanceWindow
     */
    #[Endpoint(title: 'Update maintenance window', description: 'Update a maintenance window.')]
    public function update(
        UpdateMaintenanceWindowRequest $request,
        MaintenanceWindow $maintenanceWindow,
    ): JsonResource {
        $maintenanceWindow->update($request->validated());

        return MaintenanceWindowResource::make($maintenanceWindow->refresh())->additional([
            'success' => true,
            'code'    => 200,
            'message' => 'Maintenance window updated successfully.',
        ]);
    }

    /**
     * Delete a maintenance window.
     *
     * @param MaintenanceWindow $maintenanceWindow
     */
    #[Endpoint(title: 'Delete maintenance window', description: 'Delete a maintenance window.')]
    public function destroy(MaintenanceWindow $maintenanceWindow): JsonResponse
    {
        $maintenanceWindow->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Maintenance window deleted successfully.',
        ]);
    }

    /**
     * Toggle the global indefinite pause on/off.
     *
     * When active, all providers are suppressed from running scheduled tests.
     */
    #[Endpoint(title: 'Toggle global pause', description: 'Toggle the global indefinite pause on/off. When active, all providers are suppressed from running scheduled tests.')]
    public function toggleGlobalPause(): JsonResponse
    {
        $isCurrentlyActive = MaintenanceWindow::query()
            ->active()
            ->ofType(MaintenanceWindowType::Indefinite)
            ->whereJsonContains('providers', 'all')
            ->exists();

        MaintenanceWindow::toggleGlobalPause(! $isCurrentlyActive);

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => $isCurrentlyActive
                ? 'Global pause deactivated. All providers will resume their scheduled runs.'
                : 'Global pause activated. All speedtest runs are now suppressed.',
        ]);
    }
}
