<?php

namespace Tests\Feature\Mcp;

use App\Enums\TokenAbility;
use App\Enums\WorkflowRuleMetric;
use App\Mcp\Servers\ZepeedServer;
use App\Mcp\Tools\CreateWorkflowRule;
use App\Mcp\Tools\DeleteWorkflowRule;
use App\Mcp\Tools\ListWorkflowRules;
use App\Mcp\Tools\ToggleWorkflowRule;
use App\Mcp\Tools\UpdateWorkflowRule;
use App\Models\Webhook;
use App\Models\WorkflowRule;
use App\Models\WorkflowRuleAction;
use App\Models\WorkflowRuleCondition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowRuleToolsTest extends TestCase
{
    use ActsAsMcpUser, RefreshDatabase;

    public function testListWorkflowRulesWithConditionsAndActions(): void
    {
        $user = $this->mcpUser([TokenAbility::WorkflowRulesView->value]);
        $rule = WorkflowRule::factory()->create();
        WorkflowRuleCondition::factory()->create(['workflow_rule_id' => $rule->id]);
        WorkflowRuleAction::factory()->create(['workflow_rule_id' => $rule->id]);

        $response = ZepeedServer::actingAs($user)
            ->tool(ListWorkflowRules::class, ['per_page' => 10, 'page' => 1]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->has('data', 1)
                    ->where('pagination.total', 1)
                    ->etc();
            });
    }

    public function testCreateWorkflowRuleWithConditionsAndActions(): void
    {
        $user = $this->mcpUser([TokenAbility::WorkflowRulesCreate->value]);
        $webhook = Webhook::factory()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(CreateWorkflowRule::class, [
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
                    ->where('workflow_rule.name', 'Downtime alert')
                    ->etc();
            });

        $rule = WorkflowRule::query()->where('name', 'Downtime alert')->first();

        $this->assertNotNull($rule);
        $this->assertCount(1, $rule->conditions);
        $this->assertCount(1, $rule->actions);
    }

    public function testUpdateWorkflowRuleReplacesConditionsWholesale(): void
    {
        $user = $this->mcpUser([TokenAbility::WorkflowRulesUpdate->value]);
        $rule = WorkflowRule::factory()->create();
        WorkflowRuleCondition::factory()->create([
            'workflow_rule_id' => $rule->id,
            'metric'           => 'status',
            'operator'         => 'is',
            'value'            => 'failed',
        ]);

        $response = ZepeedServer::actingAs($user)
            ->tool(UpdateWorkflowRule::class, [
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
        $this->assertSame(WorkflowRuleMetric::DownloadMbps, $rule->conditions->first()->metric);
    }

    public function testToggleWorkflowRule(): void
    {
        $user = $this->mcpUser([TokenAbility::WorkflowRulesUpdate->value]);
        $rule = WorkflowRule::factory()->create(['is_active' => true]);

        $response = ZepeedServer::actingAs($user)
            ->tool(ToggleWorkflowRule::class, ['id' => $rule->id]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('message', 'Workflow rule paused.')
                    ->etc();
            });

        $this->assertFalse($rule->refresh()->is_active);
    }

    public function testDeleteWorkflowRule(): void
    {
        $user = $this->mcpUser([TokenAbility::WorkflowRulesDelete->value]);
        $rule = WorkflowRule::factory()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(DeleteWorkflowRule::class, ['id' => $rule->id]);

        $response
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('workflow_rules', ['id' => $rule->id]);
    }
}
