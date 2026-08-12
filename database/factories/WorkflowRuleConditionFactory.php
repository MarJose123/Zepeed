<?php

namespace Database\Factories;

use App\Enums\WorkflowRuleMetric;
use App\Enums\WorkflowRuleOperator;
use App\Models\WorkflowRule;
use App\Models\WorkflowRuleCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowRuleCondition>
 */
class WorkflowRuleConditionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workflow_rule_id' => WorkflowRule::factory(),
            'metric'           => WorkflowRuleMetric::DownloadMbps,
            'operator'         => WorkflowRuleOperator::IsBelow,
            'value'            => (string) fake()->numberBetween(1, 100),
            'sort_order'       => 0,
        ];
    }
}
