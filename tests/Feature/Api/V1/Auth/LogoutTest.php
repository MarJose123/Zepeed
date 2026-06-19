<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can logout.
     */
    public function testAuthenticatedUserCanLogout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/auth/logout');

        $response
            ->assertStatus(200)
            ->assertJsonPath('message', 'Logged out successfully');
    }

    /**
     * Test that token is revoked after logout.
     */
    public function testTokenIsRevokedAfterLogout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        $plainToken = $token->plainTextToken;

        // Logout
        $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Token should be invalid now
        $response = $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->getJson('/api/v1/auth/user');

        $response
            ->assertStatus(401)
            ->assertJsonPath('type', 'AuthenticationException');
    }

    /**
     * Test that revoked token cannot be used for subsequent requests.
     */
    public function testRevokedTokenCannotBeUsedForSubsequentRequests(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        $plainToken = $token->plainTextToken;

        // Verify token works before logout
        $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->getJson('/api/v1/auth/user')
            ->assertStatus(200);

        // Logout
        $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Token should not work anymore
        $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->getJson('/api/v1/auth/user')
            ->assertStatus(401);
    }

    /**
     * Test that unauthenticated request returns 401 with API exception format.
     */
    public function testUnauthenticatedRequestReturns401WithApiExceptionFormat(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response
            ->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'message',
                'statusCode',
                'code',
                'type',
            ])
            ->assertJsonPath('type', 'AuthenticationException');
    }

    /**
     * Test that multiple tokens are independent.
     */
    public function testMultipleTokensAreIndependent(): void
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('token-1');
        $token2 = $user->createToken('token-2');

        // Logout with token1
        $this->withHeader('Authorization', "Bearer {$token1->plainTextToken}")
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Token1 should be revoked
        $this->withHeader('Authorization', "Bearer {$token1->plainTextToken}")
            ->getJson('/api/v1/auth/user')
            ->assertStatus(401);

        // Token2 should still work
        $this->withHeader('Authorization', "Bearer {$token2->plainTextToken}")
            ->getJson('/api/v1/auth/user')
            ->assertStatus(200);
    }
}
