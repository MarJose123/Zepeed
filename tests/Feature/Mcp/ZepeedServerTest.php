<?php

namespace Tests\Feature\Mcp;

use App\Enums\SpeedtestServer;
use App\Mcp\Servers\ZepeedServer;
use App\Mcp\Tools\GetAppVersion;
use App\Mcp\Tools\ListPingResults;
use App\Mcp\Tools\ListProviders;
use App\Mcp\Tools\ListSpeedtestResults;
use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\Provider;
use App\Models\SpeedResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZepeedServerTest extends TestCase
{
    use RefreshDatabase;

    public function testGetAppVersionToolReturnsStructuredResponse(): void
    {
        $response = ZepeedServer::tool(GetAppVersion::class);

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
        $user = User::factory()->create();
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
        $user = User::factory()->create();
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
        $user = User::factory()->create();
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
        $user = User::factory()->create();
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
}
