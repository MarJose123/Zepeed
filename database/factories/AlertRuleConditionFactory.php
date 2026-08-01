<?php

namespace Database\Factories;

use App\Enums\AlertRuleMetric;
use App\Enums\AlertRuleOperator;
use App\Models\AlertRule;
use App\Models\AlertRuleCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertRuleCondition>
 */
class AlertRuleConditionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'alert_rule_id' => AlertRule::factory(),
            'metric'        => AlertRuleMetric::DownloadMbps,
            'operator'      => AlertRuleOperator::IsBelow,
            'value'         => (string) fake()->numberBetween(1, 100),
            'sort_order'    => 0,
        ];
    }
}
