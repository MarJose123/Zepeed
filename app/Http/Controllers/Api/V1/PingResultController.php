<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PingResultResource;
use App\Models\PingResult;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PingResultController extends Controller
{
    /**
     * Get all ping monitoring results.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $pings = PingResult::query()
            ->with('target')
            ->orderBy('measured_at', 'desc')
            ->limit(100)
            ->get();

        return PingResultResource::collection($pings);
    }
}
