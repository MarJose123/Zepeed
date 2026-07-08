<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetAppVersion;
use App\Mcp\Tools\ListMaintenanceWindows;
use App\Mcp\Tools\ListPingResults;
use App\Mcp\Tools\ListProviders;
use App\Mcp\Tools\ListProviderSchedules;
use App\Mcp\Tools\ListSpeedtestResults;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Zepeed Monitoring')]
#[Version('1.0.0')]
#[Instructions('Query Zepeed network monitoring data including ping results, speedtest results, providers, schedules, and maintenance windows. All data tools require Sanctum API token authentication.')]
class ZepeedServer extends Server
{
    protected array $tools = [
        GetAppVersion::class,
        ListMaintenanceWindows::class,
        ListPingResults::class,
        ListProviderSchedules::class,
        ListProviders::class,
        ListSpeedtestResults::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
