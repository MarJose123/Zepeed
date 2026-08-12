<?php

namespace Database\Factories;

use App\Models\WorkflowRule;
use App\Models\WorkflowRuleAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowRuleAction>
 */
class WorkflowRuleActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workflow_rule_id'   => WorkflowRule::factory(),
            'type'               => 'email',
            'recipient_email'    => fake()->safeEmail(),
            'sort_order'         => 0,
        ];
    }
}
