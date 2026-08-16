<?php

namespace Tests\Feature\Web;

use App\Enums\TokenAbility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a user can create a token with selected abilities from the UI.
     */
    public function testUserCanCreateTokenWithSelectedAbilities(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/api-tokens', [
            'name'      => 'CI Token',
            'abilities' => [
                TokenAbility::SpeedtestView->value,
                TokenAbility::SpeedtestRun->value,
            ],
        ]);

        $response->assertRedirect();

        $token = $user->tokens()->where('name', 'CI Token')->first();

        $this->assertNotNull($token);
        $this->assertSame([
            TokenAbility::SpeedtestView->value,
            TokenAbility::SpeedtestRun->value,
        ], $token->abilities);
    }

    /**
     * Test that token creation requires at least one ability.
     */
    public function testTokenCreationRequiresAtLeastOneAbility(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/api-tokens', [
            'name'      => 'No abilities',
            'abilities' => [],
        ]);

        $response->assertSessionHasErrors('abilities');

        $this->assertSame(0, $user->tokens()->count());
    }

    /**
     * Test that token creation rejects unknown abilities.
     */
    public function testTokenCreationRejectsUnknownAbilities(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/api-tokens', [
            'name'      => 'Bad token',
            'abilities' => ['not:a-real-ability'],
        ]);

        $response->assertSessionHasErrors('abilities.0');

        $this->assertSame(0, $user->tokens()->count());
    }

    /**
     * Test that the token list exposes abilities and the ability options.
     */
    public function testTokenListIncludesAbilitiesAndOptions(): void
    {
        $user = User::factory()->create();
        $user->createToken('hook-token', [TokenAbility::WebhooksView->value]);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->get('/settings/api-tokens')
            ->assertInertia(fn (Assert $page) => $page
                ->component('account/settings/ApiTokens')
                ->has('tokens', 1)
                ->where('tokens.0.abilities', [TokenAbility::WebhooksView->value])
                ->has('abilities')
                ->has('abilities.0.module')
                ->has('abilities.0.abilities'));
    }
}
