<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AppVersionResource;

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
     * @response 200 {
     *   "data": {
     *     "version": "1.0.0",
     *     "build_date": "2024-01-15T10:30:00Z",
     *     "environment": "production",
     *     "name": "Zepeed"
     *   }
     * }
     *
     * @return AppVersionResource
     */
    public function show(): AppVersionResource
    {
        $data = (object) [
            'version'     => config('app.version'),
            'build_date'  => config('app.build_date'),
            'environment' => config('app.env'),
            'name'        => config('app.name'),
        ];

        return AppVersionResource::make($data);
    }
}
