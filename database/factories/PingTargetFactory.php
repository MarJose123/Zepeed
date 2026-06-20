<?php

namespace Database\Factories;

use App\Enums\PingStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PingTarget>
 */
class PingTargetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label'             => fake()->words(2, true),
            'host'              => fake()->ipv4(),
            'is_enabled'        => fake()->boolean(80),
            'packets'           => 4,
            'timeout_seconds'   => 5,
            'status'            => fake()->randomElement(PingStatus::cases()),
            'last_avg_ms'       => fake()->randomFloat(2, 10, 100),
            'last_loss_percent' => fake()->randomFloat(2, 0, 50),
            'last_tested_at'    => fake()->optional()->dateTimeThisMonth(),
        ];
    }

    /**
     * Indicate the target is enabled.
     */
    public function enabled(): static
    {
        return $this->state(static fn (array $attributes) => [
            'is_enabled' => true,
        ]);
    }

    /**
     * Indicate the target is disabled.
     */
    public function disabled(): static
    {
        return $this->state(static fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    /**
     * Set a specific host.
     */
    public function withHost(string $host): static
    {
        return $this->state(static fn (array $attributes) => [
            'host' => $host,
        ]);
    }
}
