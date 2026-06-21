<?php

namespace Tests\Feature\Api\V1;

use App\Models\SpeedResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpeedResultTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can list speedtest results.
     */
    public function testAuthenticatedUserCanListSpeedResults(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'provider_slug',
                        'provider_name',
                        'status',
                        'download',
                        'upload',
                        'ping',
                        'jitter',
                        'packet_loss',
                        'server_name',
                        'server_location',
                        'isp',
                        'share_url',
                        'measured_at',
                    ],
                ],
            ]);
    }

    /**
     * Test that response includes all speedtest results.
     */
    public function testResponseIncludesAllSpeedResults(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->count(5)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results');

        $response->assertOk();
        $this->assertCount(5, $response['data']);
    }

    /**
     * Test that speed metrics are correctly formatted.
     */
    public function testSpeedMetricsAreCorrectlyFormatted(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->create([
            'download_mbps' => '150.5678',
            'upload_mbps'   => '75.1234',
            'ping_ms'       => '25.9999',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results');

        $response->assertOk();
        $this->assertEquals(150.57, $response['data'][0]['download']);
        $this->assertEquals(75.12, $response['data'][0]['upload']);
        $this->assertEquals(26.0, $response['data'][0]['ping']);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/speedtest/results');

        $response->assertUnauthorized();
    }
}
