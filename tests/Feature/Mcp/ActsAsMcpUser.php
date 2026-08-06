<?php

namespace Tests\Feature\Mcp;

use App\Models\User;

trait ActsAsMcpUser
{
    /**
     * Create a user holding a Sanctum token with the given abilities.
     *
     * Pass an explicit ability list to simulate a scoped token, or omit it
     * to grant the wildcard `*` ability (passes every gate).
     *
     * @param array<int, string> $abilities
     */
    protected function mcpUser(array $abilities = ['*']): User
    {
        $user = User::factory()->create();

        $user->withAccessToken(
            $user->createToken('mcp-test', $abilities)->accessToken
        );

        return $user;
    }
}
