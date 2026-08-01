<?php

namespace Tests\Feature\Api\V1;

use App\Models\PingAlertCondition;
use App\Models\PingAlertRule;
use App\Models\PingTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PingAlertRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can list ping alert rules.
     */
    public function testAuthenticatedUserCanListPingAlertRules(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        PingAlertRule::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/ping-alerts');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'code',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'ping_target_id',
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
     * Test that an authenticated user can create a ping alert rule.
     */
    public function testAuthenticatedUserCanCreatePingAlertRule(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/ping-alerts', [
                'name'               => 'High packet loss',
                'ping_target_id'     => $target->id,
                'condition_operator' => 'and',
                'is_active'          => true,
                'cooldown_minutes'   => 10,
                'conditions'         => [
                    [
                        'metric'           => 'packet_loss',
                        'operator'         => 'is_above',
                        'value'            => '20',
                        'lookback_minutes' => 5,
                        'sort_order'       => 0,
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
            ->assertJsonPath('data.name', 'High packet loss')
            ->assertJsonPath('data.conditions.0.metric', 'packet_loss')
            ->assertJsonPath('data.actions.0.recipient_email', 'ops@example.com');

        $this->assertDatabaseHas('ping_alert_rules', ['name' => 'High packet loss']);
        $this->assertDatabaseHas('ping_alert_conditions', ['metric' => 'packet_loss']);
        $this->assertDatabaseHas('ping_alert_actions', ['recipient_email' => 'ops@example.com']);
    }

    /**
     * Test that ping alert rule creation validates the target exists.
     */
    public function testPingAlertRuleCreationValidatesTarget(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/ping-alerts', [
                'name'           => 'Bad target',
                'ping_target_id' => '00000000-0000-0000-0000-000000000000',
                'conditions'     => [],
                'actions'        => [],
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['ping_target_id']]);
    }

    /**
     * Test that an authenticated user can view a single ping alert rule.
     */
    public function testAuthenticatedUserCanViewPingAlertRule(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $rule = PingAlertRule::factory()->create(['name' => 'Latency watch']);
        PingAlertCondition::factory()->create(['ping_alert_rule_id' => $rule->id]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/ping-alerts/{$rule->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $rule->id)
            ->assertJsonPath('data.name', 'Latency watch');

        $this->assertCount(1, $response['data']['conditions']);
    }

    /**
     * Test that an authenticated user can update a ping alert rule.
     */
    public function testAuthenticatedUserCanUpdatePingAlertRule(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $rule = PingAlertRule::factory()->create(['name' => 'Old rule', 'is_active' => true]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->patchJson("/api/v1/ping-alerts/{$rule->id}", [
                'name'      => 'New rule',
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New rule')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('ping_alert_rules', ['id' => $rule->id, 'name' => 'New rule']);
    }

    /**
     * Test that an authenticated user can delete a ping alert rule.
     */
    public function testAuthenticatedUserCanDeletePingAlertRule(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $rule = PingAlertRule::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->deleteJson("/api/v1/ping-alerts/{$rule->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('ping_alert_rules', ['id' => $rule->id]);
    }

    /**
     * Test that a ping alert rule can be toggled.
     */
    public function testPingAlertRuleCanBeToggled(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $rule = PingAlertRule::factory()->create(['is_active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/ping-alerts/{$rule->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertTrue($rule->fresh()->is_active);
    }

    /**
     * Test that a missing ping alert rule returns 404.
     */
    public function testMissingPingAlertRuleReturns404(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/ping-alerts/nonexistent-uuid');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/ping-alerts');

        $response->assertUnauthorized();
    }
}
