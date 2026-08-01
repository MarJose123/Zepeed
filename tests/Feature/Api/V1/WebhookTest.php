<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can list webhooks.
     */
    public function testAuthenticatedUserCanListWebhooks(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Webhook::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/webhooks');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'code',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'url',
                        'method',
                        'has_secret',
                        'headers',
                        'timeout',
                        'retry_attempts',
                        'verify_ssl',
                        'is_active',
                        'last_fired_at',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
                'links',
            ]);

        $this->assertEquals(3, $response['meta']['total']);
        $this->assertTrue($response['success']);
    }

    /**
     * Test that the secret is never exposed by the API.
     */
    public function testSecretIsNeverExposed(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Webhook::factory()->withSecret()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/webhooks');

        $response->assertOk();
        $this->assertTrue($response['data'][0]['has_secret']);
        $this->assertArrayNotHasKey('secret', $response['data'][0]);
    }

    /**
     * Test that an authenticated user can create a webhook.
     */
    public function testAuthenticatedUserCanCreateWebhook(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/webhooks', [
                'name'           => 'CI Pipeline',
                'url'            => 'https://example.com/hooks/zepeed',
                'method'         => 'POST',
                'timeout'        => 30,
                'retry_attempts' => 3,
                'verify_ssl'     => true,
                'is_active'      => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'CI Pipeline');

        $this->assertDatabaseHas('webhooks', ['name' => 'CI Pipeline']);
    }

    /**
     * Test that webhook creation validates required fields.
     */
    public function testWebhookCreationValidatesRequiredFields(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/webhooks', [
                'name' => '',
                'url'  => 'not-a-url',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['name', 'url']]);
    }

    /**
     * Test that an authenticated user can view a single webhook.
     */
    public function testAuthenticatedUserCanViewWebhook(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $webhook = Webhook::factory()->create(['name' => 'Deploy Hook']);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $webhook->id)
            ->assertJsonPath('data.name', 'Deploy Hook');
    }

    /**
     * Test that an authenticated user can update a webhook.
     */
    public function testAuthenticatedUserCanUpdateWebhook(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $webhook = Webhook::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->patchJson("/api/v1/webhooks/{$webhook->id}", [
                'name'   => 'New Name',
                'method' => 'PUT',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('webhooks', ['id' => $webhook->id, 'name' => 'New Name']);
    }

    /**
     * Test that an authenticated user can delete a webhook.
     */
    public function testAuthenticatedUserCanDeleteWebhook(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $webhook = Webhook::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->deleteJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }

    /**
     * Test that a test delivery can be sent and is recorded.
     */
    public function testTestDeliveryIsSentAndRecorded(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $webhook = Webhook::factory()->create(['url' => 'https://example.com/hook']);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.success', true)
            ->assertJsonPath('data.status_code', 200);

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $webhook->id,
            'event'      => 'webhook.test',
            'success'    => true,
        ]);
    }

    /**
     * Test that a failed test delivery is recorded with success false.
     */
    public function testFailedTestDeliveryIsRecorded(): void
    {
        Http::fake(['*' => Http::response('server error', 500)]);

        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $webhook = Webhook::factory()->create(['url' => 'https://example.com/hook']);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertUnprocessable()
            ->assertJsonPath('data.success', false)
            ->assertJsonPath('data.status_code', 500);
    }

    /**
     * Test that a connection failure during a test delivery is recorded
     * and returned with 422 instead of surfacing as a 500.
     */
    public function testConnectionFailureIsRecordedAndReturns422(): void
    {
        Http::fake(static fn () => throw new ConnectionException('Connection refused'));

        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $webhook = Webhook::factory()->create(['url' => 'https://example.com/hook']);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.success', false)
            ->assertJsonPath('data.status_text', 'Connection failed')
            ->assertJsonPath('data.status_code', null);

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id'  => $webhook->id,
            'event'       => 'webhook.test',
            'success'     => false,
            'status_code' => null,
        ]);
    }

    /**
     * Test that delivery history is listed for a webhook.
     */
    public function testDeliveryHistoryIsListed(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $webhook = Webhook::factory()->create();
        WebhookDelivery::query()->create([
            'webhook_id'    => $webhook->id,
            'event'         => 'speedtest.completed',
            'status_code'   => 200,
            'status_text'   => 'OK',
            'duration_ms'   => 120,
            'attempt'       => 1,
            'max_attempts'  => 1,
            'success'       => true,
            'response_body' => 'ok',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/webhooks/{$webhook->id}/deliveries");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'webhook_id',
                        'event',
                        'status_code',
                        'success',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertEquals(1, $response['meta']['total']);
        $this->assertSame('speedtest.completed', $response['data'][0]['event']);
    }

    /**
     * Test that a missing webhook returns 404.
     */
    public function testMissingWebhookReturns404(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/webhooks/nonexistent-uuid');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/webhooks');

        $response->assertUnauthorized();
    }
}
