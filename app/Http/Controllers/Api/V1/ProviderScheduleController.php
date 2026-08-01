<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProviderScheduleRequest;
use App\Http\Requests\Api\V1\UpdateProviderScheduleRequest;
use App\Http\Resources\Api\ProviderScheduleResource;
use App\Models\ProviderSchedule;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

#[Group(
    name: 'Provider Schedules',
    description: 'Manage the cron-based execution schedules that drive automated speedtest runs for each provider.',
    weight: 8,
)]
/**
 * Provider Schedule Endpoints
 *
 * Manage the cron-based execution schedules that drive automated speedtest
 * runs for each provider.
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
    #[Endpoint(title: 'List provider schedules', description: 'List provider schedules with pagination, filtering, and sorting.')]
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

    /**
     * Show a single provider schedule.
     *
     * @param ProviderSchedule $providerSchedule
     */
    #[Endpoint(title: 'Show provider schedule', description: 'Show a single provider schedule.')]
    public function show(ProviderSchedule $providerSchedule): JsonResource
    {
        return ProviderScheduleResource::make($providerSchedule)->additional([
            'success' => true,
            'code'    => 200,
        ]);
    }

    /**
     * Create a provider schedule.
     *
     * @bodyParam provider_slug string required Provider slug (ookla, librespeed, netflix, cloudflare).
     * @bodyParam label string required Human-readable label. Max: 100.
     * @bodyParam cron_expression string nullable Valid cron expression. Empty/null means never scheduled.
     * @bodyParam is_enabled boolean required Whether the schedule is active.
     *
     * @param StoreProviderScheduleRequest $request
     */
    #[Endpoint(title: 'Create provider schedule', description: 'Create a provider schedule with a cron expression.')]
    public function store(StoreProviderScheduleRequest $request): JsonResponse
    {
        /** @var ProviderSchedule $schedule */
        $schedule = ProviderSchedule::query()->create($request->validated());

        return ProviderScheduleResource::make($schedule->refresh())
            ->additional([
                'success' => true,
                'code'    => 201,
                'message' => 'Provider schedule created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a provider schedule.
     *
     * @param UpdateProviderScheduleRequest $request
     * @param ProviderSchedule              $providerSchedule
     */
    #[Endpoint(title: 'Update provider schedule', description: 'Update a provider schedule.')]
    public function update(
        UpdateProviderScheduleRequest $request,
        ProviderSchedule $providerSchedule,
    ): JsonResource {
        $providerSchedule->update($request->validated());

        return ProviderScheduleResource::make($providerSchedule->refresh())->additional([
            'success' => true,
            'code'    => 200,
            'message' => 'Provider schedule updated successfully.',
        ]);
    }

    /**
     * Delete a provider schedule.
     *
     * @param ProviderSchedule $providerSchedule
     */
    #[Endpoint(title: 'Delete provider schedule', description: 'Delete a provider schedule.')]
    public function destroy(ProviderSchedule $providerSchedule): JsonResponse
    {
        $providerSchedule->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Provider schedule deleted successfully.',
        ]);
    }
}
