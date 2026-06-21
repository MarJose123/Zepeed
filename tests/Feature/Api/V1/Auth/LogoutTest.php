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

        $response->assertOk()
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
            ->postJson('/api/v1/auth/logout')->assertOk();

        // Token should be invalid now
        $response = $this->getJson('/api/v1/auth/user', [
            'Authorization' => "Bearer {$plainToken}",
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false);
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
            ->getJson('/api/v1/auth/user')->assertOk();

        // Logout
        $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->postJson('/api/v1/auth/logout')->assertOk();

        // Token should not work anymore
        $this->getJson('/api/v1/auth/user', [
            'Authorization' => "Bearer {$plainToken}",
        ])->assertUnauthorized();
    }

    /**
     * Test that unauthenticated request returns 401 with API exception format.
     */
    public function testUnauthenticatedRequestReturns401WithApiExceptionFormat(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized()
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated');
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
            ->postJson('/api/v1/auth/logout')->assertOk();

        // Token1 should be revoked
        $this->getJson('/api/v1/auth/user', [
            'Authorization' => "Bearer {$token1->plainTextToken}",
        ])->assertUnauthorized();

        // Token2 should still work
        $this->getJson('/api/v1/auth/user', [
            'Authorization' => "Bearer {$token2->plainTextToken}",
        ])->assertOk();
    }
}
