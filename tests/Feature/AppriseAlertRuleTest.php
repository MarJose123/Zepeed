<?php

namespace Tests\Feature;

use App\Enums\AlertRuleEvent;
use App\Models\AlertRule;
use App\Models\AlertRuleAction;
use App\Models\Apprise;
use App\Models\PingAlertAction;
use App\Models\PingAlertRule;
use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\SpeedResult;
use App\Models\User;
use App\Services\AlertRuleService;
use App\Services\PingAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Integration tests covering Apprise as an alert rule action destination.
 * All external Apprise requests are mocked with Http::fake().
 */
class AppriseAlertRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a speedtest alert rule with an Apprise action dispatches a
     * notification carrying the instance's tags and a status-mapped type.
     */
    public function testSpeedtestAlertRuleFiresAppriseAction(): void
    {
        $apprise = Apprise::factory()->withTags(['critical'])->create([
            'url' => 'https://apprise.test/notify/speed',
        ]);
        $rule = AlertRule::factory()->create([
            'event'     => AlertRuleEvent::RunCompletes,
            'is_active' => true,
        ]);
        AlertRuleAction::factory()->create([
            'alert_rule_id' => $rule->id,
            'type'          => 'apprise',
            'apprise_id'    => $apprise->id,
            'sort_order'    => 0,
        ]);
        $result = SpeedResult::factory()->success()->create();

        Http::fake();

        resolve(AlertRuleService::class)->evaluate($result);

        Http::assertSent(fn (Request $request) => $request->url() === $apprise->url
            && $request['title'] === 'Zepeed: Speedtest success'
            && $request['type'] === 'success'
            && $request['tag'] === 'critical'
            && str_contains($request['body'], 'Provider:'));

        $this->assertNotNull($apprise->fresh()->last_fired_at);
    }

    /**
     * Test that a ping alert rule with an Apprise action dispatches a
     * notification with a failure type and the instance's tags.
     */
    public function testPingAlertRuleFiresAppriseAction(): void
    {
        $apprise = Apprise::factory()->withTags(['ping', 'network'])->create([
            'url' => 'https://apprise.test/notify/ping',
        ]);
        $target = PingTarget::factory()->create();
        $rule = PingAlertRule::factory()->create([
            'ping_target_id' => $target->id,
            'is_active'      => true,
        ]);
        PingAlertAction::factory()->create([
            'ping_alert_rule_id' => $rule->id,
            'type'               => 'apprise',
            'apprise_id'         => $apprise->id,
            'sort_order'         => 0,
        ]);
        $result = PingResult::factory()->failed()->create([
            'ping_target_id' => $target->id,
        ]);

        Http::fake();

        resolve(PingAlertService::class)->evaluate($target, $result);

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
        $rule = AlertRule::factory()->create([
            'event'     => AlertRuleEvent::RunCompletes,
            'is_active' => true,
        ]);
        AlertRuleAction::factory()->create([
            'alert_rule_id' => $rule->id,
            'type'          => 'apprise',
            'apprise_id'    => $failing->id,
            'sort_order'    => 0,
        ]);
        AlertRuleAction::factory()->create([
            'alert_rule_id' => $rule->id,
            'type'          => 'apprise',
            'apprise_id'    => $working->id,
            'sort_order'    => 1,
        ]);
        $result = SpeedResult::factory()->success()->create();

        Http::fake([
            'https://fail.apprise.test/*' => Http::response(null, 500),
            'https://ok.apprise.test/*'   => Http::response(null, 200),
        ]);

        // Must not throw: per-action failures are isolated and logged.
        resolve(AlertRuleService::class)->evaluate($result);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://ok.apprise.test/notify');

        $this->assertNull($failing->fresh()->last_fired_at);
        $this->assertNotNull($working->fresh()->last_fired_at);
    }

    /**
     * Test that the alert rule API accepts an apprise action type.
     */
    public function testAlertRuleApiAcceptsAppriseAction(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/alerts', [
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

        $this->assertDatabaseHas('alert_rule_actions', [
            'type'       => 'apprise',
            'apprise_id' => $apprise->id,
        ]);
    }

    /**
     * Test that the ping alert rule API accepts an apprise action type.
     */
    public function testPingAlertRuleApiAcceptsAppriseAction(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/ping-alerts', [
                'name'               => 'Ping apprise alert',
                'ping_target_id'     => PingTarget::factory()->create()->id,
                'condition_operator' => 'and',
                'cooldown_minutes'   => 30,
                'conditions'         => [
                    [
                        'metric'           => 'latency_avg',
                        'operator'         => 'is_above',
                        'value'            => 100,
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

        $this->assertDatabaseHas('ping_alert_actions', [
            'type'       => 'apprise',
            'apprise_id' => $apprise->id,
        ]);
    }

    /**
     * Test that the alert rule API rejects an unknown action type.
     */
    public function testAlertRuleApiRejectsUnknownActionType(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/alerts', [
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
            ->postJson('/api/v1/alerts', [
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
