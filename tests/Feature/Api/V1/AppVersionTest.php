<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppVersionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can retrieve app version.
     */
    public function testAuthenticatedUserCanRetrieveAppVersion(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/app/version');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'version',
                    'build_date',
                    'environment',
                ],
            ]);
    }

    /**
     * Test that version structure contains expected values.
     */
    public function testVersionStructureIsCorrect(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/app/version');

        $response->assertOk();
        $this->assertNotNull($response['data']['version']);
        $this->assertNotNull($response['data']['build_date']);
        $this->assertNotNull($response['data']['environment']);
        $this->assertNotNull($response['data']['name']);
    }

    /**
     * Test that version reflects config values.
     */
    public function testVersionReflectsConfigValues(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/app/version');

        $response->assertOk();
        $this->assertEquals(config('app.version'), $response['data']['version']);
        $this->assertEquals(config('app.name'), $response['data']['name']);
        $this->assertEquals(config('app.env'), $response['data']['environment']);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/app/version');

        $response->assertUnauthorized();
    }

    /**
     * Test that invalid token returns 401.
     */
    public function testInvalidTokenReturns401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/app/version');

        $response->assertUnauthorized();
    }
}
