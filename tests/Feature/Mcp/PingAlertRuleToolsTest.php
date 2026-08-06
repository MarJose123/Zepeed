<?php

namespace Tests\Feature\Mcp;

use App\Enums\TokenAbility;
use App\Mcp\Servers\ZepeedServer;
use App\Mcp\Tools\CreatePingAlertRule;
use App\Mcp\Tools\DeletePingAlertRule;
use App\Mcp\Tools\ListPingAlertRules;
use App\Mcp\Tools\TogglePingAlertRule;
use App\Mcp\Tools\UpdatePingAlertRule;
use App\Models\PingAlertRule;
use App\Models\PingTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PingAlertRuleToolsTest extends TestCase
{
    use ActsAsMcpUser, RefreshDatabase;

    public function testCreatePingAlertRuleWithConditionsAndActions(): void
    {
        $user = $this->mcpUser([TokenAbility::PingAlertsCreate->value]);
        $target = PingTarget::factory()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(CreatePingAlertRule::class, [
                'name'               => 'Latency spike',
                'ping_target_id'     => $target->id,
                'condition_operator' => 'and',
                'cooldown_minutes'   => 60,
                'conditions'         => [
                    [
                        'metric'           => 'latency_avg',
                        'operator'         => 'is_above',
                        'value'            => 100,
                        'lookback_minutes' => 5,
                    ],
                ],
                'actions' => [
                    ['type' => 'email', 'recipient_email' => 'ops@example.com'],
                ],
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('success', true)
                    ->where('ping_alert_rule.name', 'Latency spike')
                    ->etc();
            });

        $rule = PingAlertRule::query()->where('name', 'Latency spike')->first();

        $this->assertNotNull($rule);
        $this->assertCount(1, $rule->conditions);
        $this->assertCount(1, $rule->actions);
    }

    public function testListPingAlertRules(): void
    {
        $user = $this->mcpUser([TokenAbility::PingAlertsView->value]);
        PingAlertRule::factory()->count(2)->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(ListPingAlertRules::class, ['per_page' => 10, 'page' => 1]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->has('data', 2)
                    ->where('pagination.total', 2)
                    ->etc();
            });
    }

    public function testUpdatePingAlertRule(): void
    {
        $user = $this->mcpUser([TokenAbility::PingAlertsUpdate->value]);
        $rule = PingAlertRule::factory()->create(['name' => 'Original']);

        $response = ZepeedServer::actingAs($user)
            ->tool(UpdatePingAlertRule::class, [
                'id'   => $rule->id,
                'name' => 'Updated',
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('ping_alert_rule.name', 'Updated')
                    ->etc();
            });

        $this->assertSame('Updated', $rule->refresh()->name);
    }

    public function testTogglePingAlertRule(): void
    {
        $user = $this->mcpUser([TokenAbility::PingAlertsUpdate->value]);
        $rule = PingAlertRule::factory()->create(['is_active' => false]);

        $response = ZepeedServer::actingAs($user)
            ->tool(TogglePingAlertRule::class, ['id' => $rule->id]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('message', 'Ping alert rule activated.')
                    ->etc();
            });

        $this->assertTrue($rule->refresh()->is_active);
    }

    public function testDeletePingAlertRule(): void
    {
        $user = $this->mcpUser([TokenAbility::PingAlertsDelete->value]);
        $rule = PingAlertRule::factory()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(DeletePingAlertRule::class, ['id' => $rule->id]);

        $response
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('ping_alert_rules', ['id' => $rule->id]);
    }
}
