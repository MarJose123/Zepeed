<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProviderScheduleResource;
use App\Models\ProviderSchedule;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Provider Schedule Management Endpoints
 *
 * Manages speedtest execution schedules for each provider.
 * Schedules define when and how often each provider runs speedtests.
 */
class ProviderScheduleController extends Controller
{
    /**
     * List all provider schedules.·
     *
     * Retrieves all speedtest schedules across all providers. Returns schedules
     * in reverse chronological order (most recently created first).
     * Each schedule includes the cron expression, enabled status, and which
     * provider it is associated with.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": "550e8400-e29b-41d4-a716-446655440000",
     *       "provider_id": "550e8400-e29b-41d4-a716-446655440010",
     *       "cron": "0 ·/6 · · ·",
     *       "enabled": true,
     *       "created_at": "2024-01-15T10:30:00Z"
     *     }
     *   ]
     * }
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $schedules = ProviderSchedule::query()->latest()
            ->get();

        return ProviderScheduleResource::collection($schedules);
    }
}
