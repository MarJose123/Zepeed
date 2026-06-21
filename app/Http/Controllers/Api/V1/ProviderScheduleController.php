<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProviderScheduleResource;
use App\Models\ProviderSchedule;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProviderScheduleController extends Controller
{
    /**
     * Get all provider schedules.
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
