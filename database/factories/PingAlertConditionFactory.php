<?php

namespace Database\Factories;

use App\Enums\PingAlertMetric;
use App\Enums\PingAlertOperator;
use App\Models\PingAlertCondition;
use App\Models\PingAlertRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PingAlertCondition>
 */
class PingAlertConditionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ping_alert_rule_id' => PingAlertRule::factory(),
            'metric'             => PingAlertMetric::PacketLoss,
            'operator'           => PingAlertOperator::IsAbove,
            'value'              => (string) fake()->numberBetween(1, 50),
            'lookback_minutes'   => 5,
            'sort_order'         => 0,
        ];
    }
}
