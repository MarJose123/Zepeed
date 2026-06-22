<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProviderResource;
use App\Models\Provider;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Provider Management Endpoints
 *
 * Provides access to speedtest provider information and their current status.
 * Providers are the various speedtest services (Ookla, LibreSpeed, Cloudflare, etc.)
 * configured in the system.
 */
class ProviderController extends Controller
{
    /**
     * List all configured providers.
     *
     * Retrieves all speedtest providers available in the system, including their
     * enabled/disabled status, names, and configuration. Providers are returned
     * in alphabetical order by name.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": "550e8400-e29b-41d4-a716-446655440000",
     *       "name": "Ookla",
     *       "enabled": true,
     *       "created_at": "2024-01-01T12:00:00Z"
     *     },
     *     {
     *       "id": "550e8400-e29b-41d4-a716-446655440001",
     *       "name": "LibreSpeed",
     *       "enabled": true,
     *       "created_at": "2024-01-02T12:00:00Z"
     *     }
     *   ]
     * }
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $providers = Provider::query()
            ->orderBy('name')
            ->get();

        return ProviderResource::collection($providers);
    }
}
