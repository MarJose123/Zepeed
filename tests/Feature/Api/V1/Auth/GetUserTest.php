<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can fetch their data.
     */
    public function testAuthenticatedUserCanFetchTheirData(): void
    {
        $user = User::factory()->create(['appearance' => 'dark']);
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/auth/user');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'appearance',
                    'created_at',
                ],
            ]);

        $this->assertSame($user->email, $response['data']['email']);
        $this->assertSame('dark', $response['data']['appearance']);
        $this->assertSame($user->id, $response['data']['id']);
    }

    /**
     * Test that response includes all user fields.
     */
    public function testResponseIncludesAllUserFields(): void
    {
        $user = User::factory()->create([
            'name'       => 'John Doe',
            'email'      => 'john@example.com',
            'appearance' => 'light',
        ]);
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/auth/user');

        $response->assertOk()
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.email', 'john@example.com')
            ->assertJsonPath('data.appearance', 'light');

        $this->assertNotNull($response['data']['created_at']);
    }

    /**
     * Test that unauthenticated request returns 401 with API exception format.
     */
    public function testUnauthenticatedRequestReturns401WithApiExceptionFormat(): void
    {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertUnauthorized()
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated');
    }

    /**
     * Test that invalid token returns 401.
     */
    public function testInvalidTokenReturns401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-here')
            ->getJson('/api/v1/auth/user');

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated');
    }

    /**
     * Test that expired token returns 401.
     */
    public function testExpiredTokenReturns401(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken(
            'test-token',
            ['*'],
            now()->subDay()
        );

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/auth/user');

        $response->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    /**
     * Test that missing authorization header returns 401.
     */
    public function testMissingAuthorizationHeaderReturns401(): void
    {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated');
    }

    /**
     * Test that malformed authorization header returns 401.
     */
    public function testMalformedAuthorizationHeaderReturns401(): void
    {
        $response = $this->withHeader('Authorization', 'InvalidFormat invalid-token')
            ->getJson('/api/v1/auth/user');

        $response->assertUnauthorized();
    }
}
