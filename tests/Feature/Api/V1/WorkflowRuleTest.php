<?php

namespace Tests\Feature\Api\V1;

use App\Enums\SpeedtestServer;
use App\Models\Provider;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WorkflowRule;
use App\Models\WorkflowRuleCondition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can list workflow rules.
     */
    public function testAuthenticatedUserCanListWorkflowRules(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        WorkflowRule::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/workflow-rules');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'code',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'provider_slug',
                        'event',
                        'condition_operator',
                        'is_active',
                        'cooldown_minutes',
                        'conditions',
                        'actions',
                        'created_at',
                    ],
                ],
                'meta',
                'links',
            ]);

        $this->assertEquals(3, $response['meta']['total']);
    }

    /**
     * Test that an authenticated user can create an workflow rule with
     * conditions and actions.
     */
    public function testAuthenticatedUserCanCreateWorkflowRule(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->withSlug(SpeedtestServer::Ookla)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/workflow-rules', [
                'name'               => 'Slow download',
                'provider_slug'      => 'ookla',
                'event'              => 'run_completes',
                'condition_operator' => 'and',
                'is_active'          => true,
                'cooldown_minutes'   => 15,
                'conditions'         => [
                    [
                        'metric'     => 'download_mbps',
                        'operator'   => 'is_below',
                        'value'      => '50',
                        'sort_order' => 0,
                    ],
                ],
                'actions' => [
                    [
                        'type'            => 'email',
                        'recipient_email' => 'ops@example.com',
                        'sort_order'      => 0,
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Slow download')
            ->assertJsonPath('data.conditions.0.metric', 'download_mbps')
            ->assertJsonPath('data.actions.0.type', 'email');

        $this->assertDatabaseHas('workflow_rules', ['name' => 'Slow download']);
        $this->assertDatabaseHas('workflow_rule_conditions', ['metric' => 'download_mbps']);
        $this->assertDatabaseHas('workflow_rule_actions', ['recipient_email' => 'ops@example.com']);
    }

    /**
     * Test that workflow rule creation validates the event enum.
     */
    public function testWorkflowRuleCreationValidatesEventEnum(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/workflow-rules', [
                'name'  => 'Bad event',
                'event' => 'not_an_event',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['event']]);
    }

    /**
     * Test that an authenticated user can view a single workflow rule.
     */
    public function testAuthenticatedUserCanViewWorkflowRule(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $rule = WorkflowRule::factory()->create(['name' => 'Failure alert']);
        WorkflowRuleCondition::factory()->create(['workflow_rule_id' => $rule->id]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/workflow-rules/{$rule->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $rule->id)
            ->assertJsonPath('data.name', 'Failure alert');

        $this->assertCount(1, $response['data']['conditions']);
    }

    /**
     * Test that an authenticated user can update an workflow rule.
     */
    public function testAuthenticatedUserCanUpdateWorkflowRule(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $rule = WorkflowRule::factory()->create(['name' => 'Old rule', 'is_active' => true]);
        WorkflowRuleCondition::factory()->create(['workflow_rule_id' => $rule->id]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->patchJson("/api/v1/workflow-rules/{$rule->id}", [
                'name'       => 'New rule',
                'is_active'  => false,
                'conditions' => [
                    [
                        'metric'     => 'ping_ms',
                        'operator'   => 'is_above',
                        'value'      => '200',
                        'sort_order' => 0,
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New rule')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.conditions.0.metric', 'ping_ms');

        $this->assertDatabaseHas('workflow_rules', ['id' => $rule->id, 'name' => 'New rule']);
        $this->assertDatabaseMissing('workflow_rule_conditions', ['workflow_rule_id' => $rule->id, 'metric' => 'download_mbps']);
    }

    /**
     * Test that an authenticated user can delete an workflow rule.
     */
    public function testAuthenticatedUserCanDeleteWorkflowRule(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $rule = WorkflowRule::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->deleteJson("/api/v1/workflow-rules/{$rule->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('workflow_rules', ['id' => $rule->id]);
    }

    /**
     * Test that an workflow rule can be toggled on and off.
     */
    public function testWorkflowRuleCanBeToggled(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $rule = WorkflowRule::factory()->create(['is_active' => true]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/workflow-rules/{$rule->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse($rule->fresh()->is_active);
    }

    /**
     * Test that workflow rules can be filtered by active status.
     */
    public function testWorkflowRulesCanBeFilteredByActiveStatus(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        WorkflowRule::factory()->count(2)->create(['is_active' => true]);
        WorkflowRule::factory()->count(3)->create(['is_active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/workflow-rules?is_active=1');

        $response->assertOk();
        $this->assertEquals(2, $response['meta']['total']);
    }

    /**
     * Test that a missing workflow rule returns 404.
     */
    public function testMissingWorkflowRuleReturns404(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/workflow-rules/nonexistent-uuid');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/workflow-rules');

        $response->assertUnauthorized();
    }

    /**
     * Test that a webhook action references an existing webhook.
     */
    public function testWebhookActionRequiresExistingWebhook(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $webhook = Webhook::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/workflow-rules', [
                'name'               => 'Webhook alert',
                'event'              => 'run_fails',
                'condition_operator' => 'and',
                'actions'            => [
                    [
                        'type'       => 'webhook',
                        'webhook_id' => $webhook->id,
                        'sort_order' => 0,
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.actions.0.webhook_id', $webhook->id);

        $this->assertDatabaseHas('workflow_rule_actions', [
            'type'       => 'webhook',
            'webhook_id' => $webhook->id,
        ]);
    }
}
