<?php

namespace App\Mcp\Tools\Concerns;

use App\Enums\TokenAbility;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Laravel\Mcp\Request;

trait AuthorizesRequests
{
    /**
     * Ensure the authenticated token holds at least one of the given abilities.
     *
     * Mirrors the REST API's `ability:` middleware (any-of) semantics. Write
     * operations pass a single ability, which therefore requires it explicitly,
     * matching the API's `abilities:` middleware behaviour.
     *
     * @param TokenAbility|string ...$abilities
     */
    protected function authorize(Request $request, TokenAbility|string ...$abilities): void
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException('Unauthenticated. A valid API token is required.');
        }

        $required = array_map(
            static fn (TokenAbility|string $ability): string => $ability instanceof TokenAbility ? $ability->value : $ability,
            $abilities,
        );

        foreach ($required as $ability) {
            if ($user->tokenCan($ability)) {
                return;
            }
        }

        throw new AuthorizationException(
            'This action requires one of the following token abilities: ' . implode(', ', $required) . '.'
        );
    }
}
