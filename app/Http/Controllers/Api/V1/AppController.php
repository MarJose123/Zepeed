<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AppVersionResource;

class AppController extends Controller
{
    /**
     * Get application version information.
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
