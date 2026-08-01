<?php

namespace Database\Factories;

use App\Enums\AlertRuleEvent;
use App\Models\AlertRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertRule>
 */
class AlertRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'               => fake()->sentence(3),
            'provider_slug'      => null,
            'event'              => AlertRuleEvent::RunFails,
            'condition_operator' => 'and',
            'is_active'          => true,
            'cooldown_minutes'   => 30,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
