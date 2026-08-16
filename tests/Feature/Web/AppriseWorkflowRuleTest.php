<?php

namespace Tests\Feature\Web;

use App\Enums\WorkflowRuleEvent;
use App\Models\Apprise;
use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\SpeedResult;
use App\Models\User;
use App\Models\WorkflowRule;
use App\Models\WorkflowRuleAction;
use App\Services\WorkflowRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Integration tests covering Apprise as an workflow rule action destination.
 * All external Apprise requests are mocked with Http::fake().
 */
class AppriseWorkflowRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a speedtest workflow rule with an Apprise action dispatches a
     * notification carrying the instance's tags and a status-mapped type.
     */
    public function testSpeedtestWorkflowRuleFiresAppriseAction(): void
    {
        $apprise = Apprise::factory()->withTags(['critical'])->create([
            'url' => 'https://apprise.test/notify/speed',
        ]);
        $rule = WorkflowRule::factory()->create([
            'event'     => WorkflowRuleEvent::RunCompletes,
            'is_active' => true,
        ]);
        WorkflowRuleAction::factory()->create([
            'workflow_rule_id' => $rule->id,
            'type'             => 'apprise',
            'apprise_id'       => $apprise->id,
            'sort_order'       => 0,
        ]);
        $result = SpeedResult::factory()->success()->create();

        Http::fake();

        resolve(WorkflowRuleService::class)->evaluate($result);

        Http::assertSent(fn (Request $request) => $request->url() === $apprise->url
            && $request['title'] === 'Zepeed: Speedtest success'
            && $request['type'] === 'success'
            && $request['tag'] === 'critical'
            && str_contains($request['body'], 'Provider:'));

        $this->assertNotNull($apprise->fresh()->last_fired_at);
    }

    /**
     * Test that a ping workflow rule with an Apprise action dispatches a
     * notification with a failure type and the instance's tags.
     */
    public function testPingWorkflowRuleFiresAppriseAction(): void
    {
        $apprise = Apprise::factory()->withTags(['ping', 'network'])->create([
            'url' => 'https://apprise.test/notify/ping',
        ]);
        $target = PingTarget::factory()->create();
        $rule = WorkflowRule::factory()->ping()->create([
            'ping_target_id' => $target->id,
            'is_active'      => true,
        ]);
        WorkflowRuleAction::factory()->create([
            'workflow_rule_id' => $rule->id,
            'type'             => 'apprise',
            'apprise_id'       => $apprise->id,
            'sort_order'       => 0,
        ]);
        $result = PingResult::factory()->failed()->create([
            'ping_target_id' => $target->id,
        ]);

        Http::fake();

        resolve(WorkflowRuleService::class)->evaluatePing($target, $result);

        Http::assertSent(fn (Request $request) => $request->url() === $apprise->url
            && $request['title'] === "Zepeed: Ping alert — {$rule->name}"
            && $request['type'] === 'failure'
            && $request['tag'] === 'ping,network'
            && str_contains($request['body'], 'Rule:'));

        $this->assertNotNull($apprise->fresh()->last_fired_at);
    }

    /**
     * Test that a failing Apprise action on a rule does not prevent the
     * rule's other actions from firing.
     */
    public function testFailingAppriseActionDoesNotBlockOtherActions(): void
    {
        $failing = Apprise::factory()->create(['url' => 'https://fail.apprise.test/notify']);
        $working = Apprise::factory()->create(['url' => 'https://ok.apprise.test/notify']);
        $rule = WorkflowRule::factory()->create([
            'event'     => WorkflowRuleEvent::RunCompletes,
            'is_active' => true,
        ]);
        WorkflowRuleAction::factory()->create([
            'workflow_rule_id' => $rule->id,
            'type'             => 'apprise',
            'apprise_id'       => $failing->id,
            'sort_order'       => 0,
        ]);
        WorkflowRuleAction::factory()->create([
            'workflow_rule_id' => $rule->id,
            'type'             => 'apprise',
            'apprise_id'       => $working->id,
            'sort_order'       => 1,
        ]);
        $result = SpeedResult::factory()->success()->create();

        Http::fake([
            'https://fail.apprise.test/*' => Http::response(null, 500),
            'https://ok.apprise.test/*'   => Http::response(null, 200),
        ]);

        // Must not throw: per-action failures are isolated and logged.
        resolve(WorkflowRuleService::class)->evaluate($result);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://ok.apprise.test/notify');

        $this->assertNull($failing->fresh()->last_fired_at);
        $this->assertNotNull($working->fresh()->last_fired_at);
    }

    /**
     * Test that the workflow rule API accepts an apprise action type.
     */
    public function testWorkflowRuleApiAcceptsAppriseAction(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/workflow-rules', [
                'name'               => 'Apprise alert',
                'event'              => 'run_fails',
                'condition_operator' => 'and',
                'actions'            => [
                    [
                        'type'       => 'apprise',
                        'apprise_id' => $apprise->id,
                        'sort_order' => 0,
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.actions.0.type', 'apprise')
            ->assertJsonPath('data.actions.0.apprise_id', $apprise->id);

        $this->assertDatabaseHas('workflow_rule_actions', [
            'type'       => 'apprise',
            'apprise_id' => $apprise->id,
        ]);
    }

    /**
     * Test that the ping workflow rule API accepts an apprise action type.
     */
    public function testPingWorkflowRuleApiAcceptsAppriseAction(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/workflow-rules', [
                'name'               => 'Ping apprise alert',
                'event'              => 'ping',
                'ping_target_id'     => PingTarget::factory()->create()->id,
                'condition_operator' => 'and',
                'cooldown_minutes'   => 30,
                'conditions'         => [
                    [
                        'metric'           => 'latency_avg',
                        'operator'         => 'is_above',
                        'value'            => '100',
                        'lookback_minutes' => 30,
                        'sort_order'       => 0,
                    ],
                ],
                'actions'            => [
                    [
                        'type'       => 'apprise',
                        'apprise_id' => $apprise->id,
                        'sort_order' => 0,
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.actions.0.type', 'apprise')
            ->assertJsonPath('data.actions.0.apprise_id', $apprise->id);

        $this->assertDatabaseHas('workflow_rule_actions', [
            'type'       => 'apprise',
            'apprise_id' => $apprise->id,
        ]);
    }

    /**
     * Test that the workflow rule API rejects an unknown action type.
     */
    public function testWorkflowRuleApiRejectsUnknownActionType(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/workflow-rules', [
                'name'               => 'Bad action',
                'event'              => 'run_fails',
                'condition_operator' => 'and',
                'actions'            => [
                    [
                        'type'       => 'carrier-pigeon',
                        'sort_order' => 0,
                    ],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['actions.0.type']);
    }

    /**
     * Test that an apprise action requires an existing Apprise instance.
     */
    public function testAppriseActionRequiresExistingApprise(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/workflow-rules', [
                'name'               => 'Orphaned action',
                'event'              => 'run_fails',
                'condition_operator' => 'and',
                'actions'            => [
                    [
                        'type'       => 'apprise',
                        'apprise_id' => Str::uuid()->toString(),
                        'sort_order' => 0,
                    ],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['actions.0.apprise_id']);
    }
}
