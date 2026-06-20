<?php

namespace Database\Factories;

use App\Enums\SpeedtestServer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProviderSchedule>
 */
class ProviderScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_slug'     => fake()->randomElement(SpeedtestServer::cases()),
            'label'             => fake()->word(),
            'cron_expression'   => '0 * * * *',
            'is_enabled'        => fake()->boolean(80),
            'last_scheduled_at' => null,
        ];
    }

    /**
     * Indicate the schedule is enabled.
     */
    public function enabled(): static
    {
        return $this->state(static fn (array $attributes) => [
            'is_enabled' => true,
        ]);
    }

    /**
     * Indicate the schedule is disabled.
     */
    public function disabled(): static
    {
        return $this->state(static fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    /**
     * Set a custom cron expression.
     */
    public function withCronExpression(string $expression): static
    {
        return $this->state(static fn (array $attributes) => [
            'cron_expression' => $expression,
        ]);
    }
}
