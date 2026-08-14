<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateExport;
use App\Mcp\Tools\CreateMaintenanceWindow;
use App\Mcp\Tools\CreatePingAlertRule;
use App\Mcp\Tools\CreateWebhook;
use App\Mcp\Tools\CreateWorkflowRule;
use App\Mcp\Tools\DeleteMaintenanceWindow;
use App\Mcp\Tools\DeletePingAlertRule;
use App\Mcp\Tools\DeleteWebhook;
use App\Mcp\Tools\DeleteWorkflowRule;
use App\Mcp\Tools\GetAppVersion;
use App\Mcp\Tools\GetExport;
use App\Mcp\Tools\ListExports;
use App\Mcp\Tools\ListMaintenanceWindows;
use App\Mcp\Tools\ListPingAlertRules;
use App\Mcp\Tools\ListPingResults;
use App\Mcp\Tools\ListProviders;
use App\Mcp\Tools\ListProviderSchedules;
use App\Mcp\Tools\ListSpeedtestResults;
use App\Mcp\Tools\ListWebhooks;
use App\Mcp\Tools\ListWorkflowRules;
use App\Mcp\Tools\RunSpeedtest;
use App\Mcp\Tools\TestWebhook;
use App\Mcp\Tools\ToggleGlobalPause;
use App\Mcp\Tools\TogglePingAlertRule;
use App\Mcp\Tools\ToggleWorkflowRule;
use App\Mcp\Tools\UpdateMaintenanceWindow;
use App\Mcp\Tools\UpdatePingAlertRule;
use App\Mcp\Tools\UpdateProvider;
use App\Mcp\Tools\UpdateWebhook;
use App\Mcp\Tools\UpdateWorkflowRule;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Zepeed Monitoring')]
#[Version('2.0.0')]
#[Instructions('Query and manage Zepeed network monitoring data: ping results, speedtest results, providers and schedules, maintenance windows, webhooks, workflow rules, and exports. Every tool requires a Sanctum API token and enforces the same token abilities as the REST API — read tools accept the module\'s view/create/update/delete abilities, while write tools require their specific ability (e.g. speedtest:run, webhooks:create).')]
class ZepeedServer extends Server
{
    protected array $tools = [
        GetAppVersion::class,
        ListMaintenanceWindows::class,
        CreateMaintenanceWindow::class,
        UpdateMaintenanceWindow::class,
        DeleteMaintenanceWindow::class,
        ToggleGlobalPause::class,
        ListPingResults::class,
        ListProviderSchedules::class,
        ListProviders::class,
        UpdateProvider::class,
        RunSpeedtest::class,
        ListSpeedtestResults::class,
        ListWebhooks::class,
        CreateWebhook::class,
        UpdateWebhook::class,
        DeleteWebhook::class,
        TestWebhook::class,
        ListWorkflowRules::class,
        CreateWorkflowRule::class,
        UpdateWorkflowRule::class,
        DeleteWorkflowRule::class,
        ToggleWorkflowRule::class,
        ListPingAlertRules::class,
        CreatePingAlertRule::class,
        UpdatePingAlertRule::class,
        DeletePingAlertRule::class,
        TogglePingAlertRule::class,
        ListExports::class,
        CreateExport::class,
        GetExport::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
