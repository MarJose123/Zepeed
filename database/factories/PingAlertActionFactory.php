<?php

namespace Database\Factories;

use App\Models\PingAlertAction;
use App\Models\PingAlertRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PingAlertAction>
 */
class PingAlertActionFactory extends Factory
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
            'type'               => 'email',
            'recipient_email'    => fake()->safeEmail(),
            'sort_order'         => 0,
        ];
    }
}
