<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AppVersionResource;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\JsonResource;

#[Group(
    name: 'App',
    description: 'Provides access to application metadata and version information.',
    weight: 1,
)]
/**
 * Application Information Endpoints
 *
 * Provides access to application metadata and version information.
 */
class AppController extends Controller
{
    /**
     * Get application version and metadata.
     *
     * Retrieves current application version, build date, environment, and name.
     * Useful for clients to verify compatibility and display app information.
     *
     * @queryParam none No query parameters accepted.
     */
    #[Endpoint(title: 'Get application version', description: 'Retrieve current application version, build date, environment, and name. Useful for clients to verify compatibility and display app information.')]
    public function show(): JsonResource
    {
        $data = (object) [
            'version'     => config('app.version'),
            'build_date'  => config('app.build_date'),
            'environment' => config('app.env'),
            'name'        => config('app.name'),
        ];

        return AppVersionResource::make($data)->additional([
            'success' => true,
            'code'    => 200,
        ]);
    }
}
