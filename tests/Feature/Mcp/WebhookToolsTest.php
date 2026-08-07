<?php

namespace Tests\Feature\Mcp;

use App\Enums\TokenAbility;
use App\Mcp\Servers\ZepeedServer;
use App\Mcp\Tools\CreateWebhook;
use App\Mcp\Tools\DeleteWebhook;
use App\Mcp\Tools\ListWebhooks;
use App\Mcp\Tools\TestWebhook;
use App\Mcp\Tools\UpdateWebhook;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookToolsTest extends TestCase
{
    use ActsAsMcpUser, RefreshDatabase;

    public function testCreateWebhook(): void
    {
        $user = $this->mcpUser([TokenAbility::WebhooksCreate->value]);

        $response = ZepeedServer::actingAs($user)
            ->tool(CreateWebhook::class, [
                'name'    => 'Status page',
                'url'     => 'https://example.com/hooks/zepeed',
                'method'  => 'POST',
                'timeout' => 15,
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('success', true)
                    ->where('webhook.name', 'Status page')
                    ->etc();
            });

        $this->assertDatabaseHas('webhooks', ['name' => 'Status page']);
    }

    public function testListWebhooks(): void
    {
        $user = $this->mcpUser([TokenAbility::WebhooksView->value]);
        Webhook::factory()->count(3)->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(ListWebhooks::class, ['per_page' => 10, 'page' => 1]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->has('data', 3)
                    ->where('pagination.total', 3)
                    ->etc();
            });
    }

    public function testWebhookSecretIsNeverExposed(): void
    {
        $user = $this->mcpUser([TokenAbility::WebhooksView->value]);
        $webhook = Webhook::factory()->withSecret()->create(['name' => 'Secret webhook']);

        $response = ZepeedServer::actingAs($user)
            ->tool(ListWebhooks::class, ['per_page' => 10, 'page' => 1]);

        $response
            ->assertOk()
            ->assertHasNoErrors();

        // The tool's text content is the JSON-encoded structured payload (the
        // wire form) — the signing secret must never appear in it, only `has_secret`.
        $response
            ->assertSee('"has_secret":true')
            ->assertDontSee($webhook->secret);
    }

    public function testUpdateWebhook(): void
    {
        $user = $this->mcpUser([TokenAbility::WebhooksUpdate->value]);
        $webhook = Webhook::factory()->create(['name' => 'Original']);

        $response = ZepeedServer::actingAs($user)
            ->tool(UpdateWebhook::class, [
                'id'   => $webhook->id,
                'name' => 'Renamed',
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('webhook.name', 'Renamed')
                    ->etc();
            });

        $this->assertSame('Renamed', $webhook->refresh()->name);
    }

    public function testDeleteWebhook(): void
    {
        $user = $this->mcpUser([TokenAbility::WebhooksDelete->value]);
        $webhook = Webhook::factory()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(DeleteWebhook::class, ['id' => $webhook->id]);

        $response
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }

    public function testTestWebhookRecordsSuccessfulDelivery(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $user = $this->mcpUser([TokenAbility::WebhooksTest->value]);
        $webhook = Webhook::factory()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(TestWebhook::class, ['id' => $webhook->id]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('success', true)
                    ->where('delivery.success', true)
                    ->etc();
            });

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'event'      => 'webhook.test',
            'success'    => true,
        ]);
    }

    public function testCreateWebhookRejectsInvalidUrl(): void
    {
        $user = $this->mcpUser([TokenAbility::WebhooksCreate->value]);

        $response = ZepeedServer::actingAs($user)
            ->tool(CreateWebhook::class, [
                'name'   => 'Broken',
                'url'    => 'not-a-url',
                'method' => 'POST',
            ]);

        $response->assertHasErrors();
        $this->assertDatabaseMissing('webhooks', ['name' => 'Broken']);
    }

    public function testTestWebhookReportsConnectionFailure(): void
    {
        Http::fake(['*' => Http::response('connection error', 500)]);

        $user = $this->mcpUser([TokenAbility::WebhooksTest->value]);
        $webhook = Webhook::factory()->create(['timeout' => 1]);

        $response = ZepeedServer::actingAs($user)
            ->tool(TestWebhook::class, ['id' => $webhook->id]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('success', false)
                    ->where('delivery.success', false)
                    ->etc();
            });
    }
}
