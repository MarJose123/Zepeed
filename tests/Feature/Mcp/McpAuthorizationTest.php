<?php

namespace Tests\Feature\Mcp;

use App\Enums\TokenAbility;
use App\Mcp\Servers\ZepeedServer;
use App\Mcp\Tools\GetAppVersion;
use App\Mcp\Tools\ListPingResults;
use App\Mcp\Tools\ListWebhooks;
use App\Mcp\Tools\RunSpeedtest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpAuthorizationTest extends TestCase
{
    use ActsAsMcpUser, RefreshDatabase;

    public function testToolRequiresAuthenticatedUser(): void
    {
        $response = ZepeedServer::tool(GetAppVersion::class);

        $response->assertHasErrors();
    }

    public function testToolRejectsTokenWithoutRequiredAbility(): void
    {
        $user = $this->mcpUser([TokenAbility::WebhooksCreate->value]);

        $response = ZepeedServer::actingAs($user)
            ->tool(GetAppVersion::class);

        $response->assertHasErrors();
    }

    public function testToolRejectsTokenWithNoAbilities(): void
    {
        $user = $this->mcpUser([]);

        $response = ZepeedServer::actingAs($user)
            ->tool(ListPingResults::class);

        $response->assertHasErrors();
    }

    public function testWriteToolRequiresItsSpecificAbility(): void
    {
        // Holds speedtest:view but not speedtest:run.
        $user = $this->mcpUser([TokenAbility::SpeedtestView->value]);

        $response = ZepeedServer::actingAs($user)
            ->tool(RunSpeedtest::class, ['provider_slug' => 'ookla']);

        $response->assertHasErrors();
    }

    public function testReadToolAcceptsAnyModuleAbility(): void
    {
        // ListWebhooks accepts any webhooks module ability (any-of semantics),
        // mirroring the API's `ability:webhooks:view,...,webhooks:test` middleware.
        $user = $this->mcpUser([TokenAbility::WebhooksUpdate->value]);

        $response = ZepeedServer::actingAs($user)
            ->tool(ListWebhooks::class);

        $response->assertOk();
    }
}
