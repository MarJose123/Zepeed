<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MaintenanceWindowResource;
use App\Models\MaintenanceWindow;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MaintenanceController extends Controller
{
    /**
     * Get all maintenance windows.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $windows = MaintenanceWindow::query()
            ->orderBy('starts_at', 'desc')
            ->get();

        return MaintenanceWindowResource::collection($windows);
    }
}
