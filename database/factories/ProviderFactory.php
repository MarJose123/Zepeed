<?php

namespace Database\Factories;

use App\Enums\SpeedtestServer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Provider>
 */
class ProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug'             => fake()->randomElement(SpeedtestServer::cases()),
            'name'             => fake()->word(),
            'is_enabled'       => fake()->boolean(80),
            'alert_on_failure' => fake()->boolean(50),
            'server_url'       => null,
            'server_id'        => null,
            'extra_flags'      => null,
            'meta'             => null,
            'last_run_status'  => fake()->randomElement(['success', 'failed', 'skipped']),
            'last_run_at'      => fake()->dateTimeThisMonth(),
        ];
    }

    /**
     * Indicate the provider is enabled.
     */
    public function enabled(): static
    {
        return $this->state(static fn (array $attributes) => [
            'is_enabled' => true,
        ]);
    }

    /**
     * Indicate the provider is disabled.
     */
    public function disabled(): static
    {
        return $this->state(static fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    /**
     * Set a specific provider slug.
     */
    public function withSlug(SpeedtestServer $slug): static
    {
        return $this->state(static fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }
}
