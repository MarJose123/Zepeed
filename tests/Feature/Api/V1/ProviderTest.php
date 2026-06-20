<?php

namespace Tests\Feature\Api\V1;

use App\Enums\SpeedtestServer;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can list all providers.
     */
    public function testAuthenticatedUserCanListProviders(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->withSlug(SpeedtestServer::Ookla)->create();
        Provider::factory()->withSlug(SpeedtestServer::Cloudflare)->create();
        Provider::factory()->withSlug(SpeedtestServer::Netflix)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'slug',
                        'name',
                        'enabled',
                        'is_healthy',
                        'last_run_at',
                        'last_run_status',
                        'alert_on_failure',
                    ],
                ],
            ]);
    }

    /**
     * Test that response includes all provider records.
     */
    public function testResponseIncludesAllProviders(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->withSlug(SpeedtestServer::Ookla)->create();
        Provider::factory()->withSlug(SpeedtestServer::Cloudflare)->create();
        Provider::factory()->withSlug(SpeedtestServer::Netflix)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers');

        $response->assertOk();
        $this->assertCount(3, $response['data']);
    }

    /**
     * Test that enabled status is correctly represented.
     */
    public function testEnabledStatusIsCorrect(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->withSlug(SpeedtestServer::Ookla)->enabled()->create();
        Provider::factory()->withSlug(SpeedtestServer::Cloudflare)->disabled()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers');

        $response->assertOk();

        $enabled = collect($response['data'])->where('enabled', true)->count();
        $disabled = collect($response['data'])->where('enabled', false)->count();

        $this->assertEquals(1, $enabled);
        $this->assertEquals(1, $disabled);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/providers');

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated');
    }

    /**
     * Test that invalid token returns 401.
     */
    public function testInvalidTokenReturns401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/providers');

        $response->assertUnauthorized();
    }
}
