<?php

namespace Tests\Feature\Mcp;

use App\Enums\AlertRuleMetric;
use App\Enums\TokenAbility;
use App\Mcp\Servers\ZepeedServer;
use App\Mcp\Tools\CreateAlertRule;
use App\Mcp\Tools\DeleteAlertRule;
use App\Mcp\Tools\ListAlertRules;
use App\Mcp\Tools\ToggleAlertRule;
use App\Mcp\Tools\UpdateAlertRule;
use App\Models\AlertRule;
use App\Models\AlertRuleAction;
use App\Models\AlertRuleCondition;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertRuleToolsTest extends TestCase
{
    use ActsAsMcpUser, RefreshDatabase;

    public function testListAlertRulesWithConditionsAndActions(): void
    {
        $user = $this->mcpUser([TokenAbility::AlertsView->value]);
        $rule = AlertRule::factory()->create();
        AlertRuleCondition::factory()->create(['alert_rule_id' => $rule->id]);
        AlertRuleAction::factory()->create(['alert_rule_id' => $rule->id]);

        $response = ZepeedServer::actingAs($user)
            ->tool(ListAlertRules::class, ['per_page' => 10, 'page' => 1]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->has('data', 1)
                    ->where('pagination.total', 1)
                    ->etc();
            });
    }

    public function testCreateAlertRuleWithConditionsAndActions(): void
    {
        $user = $this->mcpUser([TokenAbility::AlertsCreate->value]);
        $webhook = Webhook::factory()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(CreateAlertRule::class, [
                'name'               => 'Downtime alert',
                'event'              => 'run_fails',
                'condition_operator' => 'and',
                'conditions'         => [
                    ['metric' => 'status', 'operator' => 'is', 'value' => 'failed'],
                ],
                'actions' => [
                    ['type' => 'webhook', 'webhook_id' => $webhook->id],
                ],
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('success', true)
                    ->where('alert_rule.name', 'Downtime alert')
                    ->etc();
            });

        $rule = AlertRule::query()->where('name', 'Downtime alert')->first();

        $this->assertNotNull($rule);
        $this->assertCount(1, $rule->conditions);
        $this->assertCount(1, $rule->actions);
    }

    public function testUpdateAlertRuleReplacesConditionsWholesale(): void
    {
        $user = $this->mcpUser([TokenAbility::AlertsUpdate->value]);
        $rule = AlertRule::factory()->create();
        AlertRuleCondition::factory()->create([
            'alert_rule_id' => $rule->id,
            'metric'        => 'status',
            'operator'      => 'is',
            'value'         => 'failed',
        ]);

        $response = ZepeedServer::actingAs($user)
            ->tool(UpdateAlertRule::class, [
                'id'         => $rule->id,
                'name'       => 'Renamed alert',
                'conditions' => [
                    ['metric' => 'download_mbps', 'operator' => 'is_below', 'value' => '10'],
                ],
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors();

        $rule->refresh();

        $this->assertSame('Renamed alert', $rule->name);
        $this->assertCount(1, $rule->conditions);
        $this->assertSame(AlertRuleMetric::DownloadMbps, $rule->conditions->first()->metric);
    }

    public function testToggleAlertRule(): void
    {
        $user = $this->mcpUser([TokenAbility::AlertsUpdate->value]);
        $rule = AlertRule::factory()->create(['is_active' => true]);

        $response = ZepeedServer::actingAs($user)
            ->tool(ToggleAlertRule::class, ['id' => $rule->id]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('message', 'Alert rule paused.')
                    ->etc();
            });

        $this->assertFalse($rule->refresh()->is_active);
    }

    public function testDeleteAlertRule(): void
    {
        $user = $this->mcpUser([TokenAbility::AlertsDelete->value]);
        $rule = AlertRule::factory()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(DeleteAlertRule::class, ['id' => $rule->id]);

        $response
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('alert_rules', ['id' => $rule->id]);
    }
}
