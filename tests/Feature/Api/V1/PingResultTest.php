<?php

namespace Tests\Feature\Api\V1;

use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PingResultTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can list ping results.
     */
    public function testAuthenticatedUserCanListPingResults(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();
        PingResult::factory()->count(3)->for($target, 'target')->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'ping_target_id',
                        'target_label',
                        'target_host',
                        'status',
                        'status_label',
                        'packets_sent',
                        'packets_received',
                        'packet_loss_percent',
                        'min_ms',
                        'avg_ms',
                        'max_ms',
                        'stddev_ms',
                        'measured_at',
                    ],
                ],
            ]);
    }

    /**
     * Test that response includes all ping results.
     */
    public function testResponseIncludesAllPingResults(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();
        PingResult::factory()->count(5)->for($target, 'target')->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings');

        $response->assertOk();
        $this->assertCount(5, $response['data']);
    }

    /**
     * Test that status is correctly represented.
     */
    public function testStatusIsCorrectlyRepresented(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();
        PingResult::factory()->for($target, 'target')->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings');

        $response->assertOk();
        $this->assertNotNull($response['data'][0]['status']);
        $this->assertNotNull($response['data'][0]['status_label']);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/pings');

        $response->assertUnauthorized();
    }
}
