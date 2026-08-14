<?php

namespace Tests\Feature\Api\V1;

use App\Enums\SpeedtestServer;
use App\Enums\TokenAbility;
use App\Models\Provider;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WorkflowRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TokenAbilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a token with the matching ability can access the endpoint.
     */
    public function testTokenWithMatchingAbilityCanAccessEndpoint(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbility::SpeedtestView->value]);

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results')
            ->assertOk();
    }

    /**
     * Test that a token without the required ability receives 403.
     */
    public function testTokenWithoutAbilityIsForbidden(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbility::WorkflowRulesView->value]);

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    /**
     * Test that a write ability implicitly grants view access.
     */
    public function testWriteAbilityImpliesView(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbility::WorkflowRulesUpdate->value]);

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/workflow-rules')
            ->assertOk();
    }

    /**
     * Test that a view ability cannot perform write operations.
     */
    public function testViewAbilityCannotWrite(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbility::WorkflowRulesView->value]);

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/workflow-rules', [
                'name'               => 'Unauthorized rule',
                'event'              => 'run_fails',
                'condition_operator' => 'and',
                'actions'            => [
                    ['type' => 'email', 'recipient_email' => 'ops@example.com'],
                ],
            ])
            ->assertForbidden();
    }

    /**
     * Test that a token with the create ability can perform create operations.
     */
    public function testCreateAbilityCanWrite(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbility::WebhooksCreate->value]);

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/webhooks', [
                'name'   => 'Deploy Hook',
                'url'    => 'https://example.com/hook',
                'method' => 'POST',
            ])
            ->assertCreated();
    }

    /**
     * Test that a create ability does not grant update operations.
     */
    public function testCreateAbilityCannotUpdate(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbility::WorkflowRulesCreate->value]);

        $rule = WorkflowRule::factory()->create();

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->patchJson("/api/v1/workflow-rules/{$rule->id}", [
                'name' => 'Attempted rename',
            ])
            ->assertForbidden();
    }

    /**
     * Test that the update ability grants update operations.
     */
    public function testUpdateAbilityCanUpdate(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbility::WorkflowRulesUpdate->value]);

        $rule = WorkflowRule::factory()->create(['name' => 'Old name']);

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->patchJson("/api/v1/workflow-rules/{$rule->id}", [
                'name' => 'New name',
            ])
            ->assertOk();
    }

    /**
     * Test that the delete ability grants delete operations.
     */
    public function testDeleteAbilityCanDelete(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbility::WorkflowRulesDelete->value]);

        $rule = WorkflowRule::factory()->create();

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->deleteJson("/api/v1/workflow-rules/{$rule->id}")
            ->assertOk();

        $this->assertDatabaseMissing('workflow_rules', ['id' => $rule->id]);
    }

    /**
     * Test that an update ability cannot delete.
     */
    public function testUpdateAbilityCannotDelete(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbility::WorkflowRulesUpdate->value]);

        $rule = WorkflowRule::factory()->create();

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->deleteJson("/api/v1/workflow-rules/{$rule->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('workflow_rules', ['id' => $rule->id]);
    }

    /**
     * Test that the webhook test action requires the dedicated test ability.
     */
    public function testWebhookTestWithoutTestAbilityIsForbidden(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbility::WebhooksCreate->value]);

        $webhook = Webhook::factory()->create();

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/webhooks/{$webhook->id}/test")
            ->assertForbidden();
    }

    /**
     * Test that the webhook test action is accepted with the test ability.
     */
    public function testWebhookTestWithTestAbilitySucceeds(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $user = User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbility::WebhooksTest->value]);

        $webhook = Webhook::factory()->create();

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/webhooks/{$webhook->id}/test")
            ->assertOk();
    }

    /**
     * Test that a token created without explicit abilities (wildcard)
     * retains full access — backwards compatible with existing tokens.
     */
    public function testWildcardTokenHasFullAccess(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/webhooks')
            ->assertOk();
    }

    /**
     * Test that a wildcard token can also perform write operations.
     */
    public function testWildcardTokenCanWrite(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/webhooks', [
                'name'   => 'Deploy Hook',
                'url'    => 'https://example.com/hook',
                'method' => 'POST',
            ])
            ->assertCreated();
    }

    /**
     * Test that a legacy token explicitly stored with the '*' ability can
     * trigger a manual speedtest run — proving backward compatibility on
     * an all-of (abilities:) route beyond the default createToken path.
     */
    public function testWildcardTokenCanRunSpeedtest(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $user->createToken('legacy-token', ['*']);

        $provider = Provider::factory()->withSlug(SpeedtestServer::Ookla)->create(['is_enabled' => true]);

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/providers/{$provider->slug->value}/run-now")
            ->assertStatus(202);

        $this->assertSame(['*'], $user->tokens()->first()->abilities);
    }

    /**
     * Test that the manual speedtest run requires the dedicated run ability.
     */
    public function testRunSpeedtestWithoutRunAbilityIsForbidden(): void
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->withSlug(SpeedtestServer::Ookla)->create(['is_enabled' => true]);

        $token = $user->createToken('providers-only', [TokenAbility::ProvidersUpdate->value]);

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/providers/{$provider->slug->value}/run-now")
            ->assertForbidden();
    }

    /**
     * Test that the manual speedtest run is accepted with the run ability.
     */
    public function testRunSpeedtestWithRunAbilityIsAccepted(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $provider = Provider::factory()->withSlug(SpeedtestServer::Ookla)->create(['is_enabled' => true]);

        $token = $user->createToken('run-token', [TokenAbility::SpeedtestRun->value]);

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/providers/{$provider->slug->value}/run-now")
            ->assertStatus(202);
    }

    /**
     * Test that the app version endpoint requires the app:view ability.
     */
    public function testAppVersionWithoutAbilityIsForbidden(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('no-app', [TokenAbility::WebhooksView->value]);

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/app/version')
            ->assertForbidden();
    }

    /**
     * Test that the app version endpoint is accessible with the app:view ability.
     */
    public function testAppVersionWithAbilityIsAccessible(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('with-app', [TokenAbility::AppView->value]);

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/app/version')
            ->assertOk();
    }
}
