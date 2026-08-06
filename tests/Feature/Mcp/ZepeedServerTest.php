<?php

namespace Tests\Feature\Mcp;

use App\Enums\SpeedtestServer;
use App\Enums\TokenAbility;
use App\Mcp\Servers\ZepeedServer;
use App\Mcp\Tools\GetAppVersion;
use App\Mcp\Tools\ListMaintenanceWindows;
use App\Mcp\Tools\ListPingResults;
use App\Mcp\Tools\ListProviders;
use App\Mcp\Tools\ListProviderSchedules;
use App\Mcp\Tools\ListSpeedtestResults;
use App\Models\MaintenanceWindow;
use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Models\SpeedResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZepeedServerTest extends TestCase
{
    use ActsAsMcpUser, RefreshDatabase;

    public function testGetAppVersionToolReturnsStructuredResponse(): void
    {
        $user = $this->mcpUser([TokenAbility::AppView->value]);

        $response = ZepeedServer::actingAs($user)
            ->tool(GetAppVersion::class);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('name', config('app.name'))
                    ->where('version', config('app.version'))
                    ->etc();
            });
    }

    public function testListPingResultsToolReturnsPaginatedData(): void
    {
        $user = $this->mcpUser([TokenAbility::PingResultsView->value]);
        $target = PingTarget::factory()->create();
        PingResult::factory()->count(3)->create(['ping_target_id' => $target->id]);

        $response = ZepeedServer::actingAs($user)
            ->tool(ListPingResults::class, [
                'per_page' => 10,
                'page'     => 1,
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->has('data', 3)
                    ->where('pagination.total', 3)
                    ->etc();
            });
    }

    public function testListSpeedtestResultsToolReturnsPaginatedData(): void
    {
        $user = $this->mcpUser([TokenAbility::SpeedtestView->value]);
        $slug = SpeedtestServer::cases()[0];
        Provider::factory()->create(['slug' => $slug]);
        SpeedResult::factory()->count(2)->create(['provider_slug' => $slug]);

        $response = ZepeedServer::actingAs($user)
            ->tool(ListSpeedtestResults::class, [
                'per_page' => 10,
                'page'     => 1,
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->has('data', 2)
                    ->where('pagination.total', 2)
                    ->etc();
            });
    }

    public function testListProvidersToolReturnsPaginatedData(): void
    {
        $user = $this->mcpUser([TokenAbility::ProvidersView->value]);
        $cases = SpeedtestServer::cases();
        foreach ($cases as $slug) {
            Provider::factory()->withSlug($slug)->create();
        }

        $response = ZepeedServer::actingAs($user)
            ->tool(ListProviders::class, [
                'per_page' => 10,
                'page'     => 1,
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->has('data', 4)
                    ->where('pagination.total', 4)
                    ->etc();
            });
    }

    public function testListPingResultsRespectsPagination(): void
    {
        $user = $this->mcpUser([TokenAbility::PingResultsView->value]);
        $target = PingTarget::factory()->create();
        PingResult::factory()->count(5)->create(['ping_target_id' => $target->id]);

        $response = ZepeedServer::actingAs($user)
            ->tool(ListPingResults::class, [
                'per_page' => 2,
                'page'     => 1,
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->has('data', 2)
                    ->where('pagination.total', 5)
                    ->where('pagination.last_page', 3)
                    ->etc();
            });
    }

    public function testListMaintenanceWindowsToolReturnsPaginatedData(): void
    {
        $user = $this->mcpUser([TokenAbility::MaintenanceView->value]);
        MaintenanceWindow::factory()->count(2)->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(ListMaintenanceWindows::class, [
                'per_page' => 10,
                'page'     => 1,
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->has('data', 2)
                    ->where('pagination.total', 2)
                    ->etc();
            });
    }

    public function testListProviderSchedulesToolReturnsPaginatedData(): void
    {
        $user = $this->mcpUser([TokenAbility::SchedulesView->value]);
        ProviderSchedule::factory()->count(3)->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(ListProviderSchedules::class, [
                'per_page' => 10,
                'page'     => 1,
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->has('data', 3)
                    ->where('pagination.total', 3)
                    ->etc();
            });
    }

    public function testListProviderSchedulesFiltersByProviderSlug(): void
    {
        $user = $this->mcpUser([TokenAbility::SchedulesView->value]);
        ProviderSchedule::factory()->create(['provider_slug' => SpeedtestServer::Ookla]);
        ProviderSchedule::factory()->create(['provider_slug' => SpeedtestServer::Cloudflare]);

        $response = ZepeedServer::actingAs($user)
            ->tool(ListProviderSchedules::class, [
                'provider_slug' => 'ookla',
                'per_page'      => 10,
                'page'          => 1,
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->has('data', 1)
                    ->where('pagination.total', 1)
                    ->etc();
            });
    }
}
