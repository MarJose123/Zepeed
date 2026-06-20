<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SpeedResultResource;
use App\Models\SpeedResult;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SpeedResultController extends Controller
{
    /**
     * Get all speedtest results.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $results = SpeedResult::query()
            ->orderBy('measured_at', 'desc')
            ->limit(100)
            ->get();

        return SpeedResultResource::collection($results);
    }
}
