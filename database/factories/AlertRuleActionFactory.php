<?php

namespace Database\Factories;

use App\Models\AlertRule;
use App\Models\AlertRuleAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertRuleAction>
 */
class AlertRuleActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'alert_rule_id'   => AlertRule::factory(),
            'type'            => 'email',
            'recipient_email' => fake()->safeEmail(),
            'sort_order'      => 0,
        ];
    }
}
