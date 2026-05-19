<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenanceMiddleware extends Middleware
{
    /**
     * The URIs that should be accessible even when maintenance mode is enabled.
     * This ensures admins can always reach the settings page to disable maintenance.
     *
     * @var list<string>
     */
    protected $except = [
        'speedtest/settings/general',
        'speedtest/settings/general/*',
    ];

}
