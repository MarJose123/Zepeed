<?php

namespace Database\Factories;

use App\Enums\WorkflowRuleEvent;
use App\Models\PingTarget;
use App\Models\WorkflowRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowRule>
 */
class WorkflowRuleFactory extends Factory
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
            'event'              => WorkflowRuleEvent::RunFails,
            'condition_operator' => 'and',
            'is_active'          => true,
            'cooldown_minutes'   => 30,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function ping(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_slug'  => null,
            'ping_target_id' => PingTarget::factory(),
            'event'          => WorkflowRuleEvent::Ping,
        ]);
    }
}
