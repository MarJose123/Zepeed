<?php

namespace Database\Factories;

use App\Enums\MaintenanceWindowType;
use App\Enums\SpeedtestServer;
use App\Models\MaintenanceWindow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceWindow>
 */
class MaintenanceWindowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label'            => fake()->sentence(3),
            'type'             => MaintenanceWindowType::Indefinite,
            'is_active'        => fake()->boolean(60),
            'providers'        => [fake()->randomElement(SpeedtestServer::cases())->value],
            'starts_at'        => null,
            'ends_at'          => null,
            'cron_expression'  => null,
            'duration_minutes' => null,
            'notes'            => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate an active maintenance window.
     */
    public function active(): static
    {
        return $this->state(static fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate an inactive maintenance window.
     */
    public function inactive(): static
    {
        return $this->state(static fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a one-time maintenance window.
     */
    public function oneTime(): static
    {
        return $this->state(static fn (array $attributes) => [
            'type'             => MaintenanceWindowType::OneTime,
            'starts_at'        => fake()->dateTimeThisMonth(),
            'ends_at'          => fake()->dateTimeThisMonth(),
            'cron_expression'  => null,
            'duration_minutes' => null,
        ]);
    }

    /**
     * Create a recurring maintenance window.
     */
    public function recurring(): static
    {
        return $this->state(static fn (array $attributes) => [
            'type'             => MaintenanceWindowType::Recurring,
            'cron_expression'  => '0 2 * * *',
            'duration_minutes' => 60,
            'starts_at'        => null,
            'ends_at'          => null,
        ]);
    }

    /**
     * Create an indefinite maintenance window.
     */
    public function indefinite(): static
    {
        return $this->state(static fn (array $attributes) => [
            'type'             => MaintenanceWindowType::Indefinite,
            'starts_at'        => null,
            'ends_at'          => null,
            'cron_expression'  => null,
            'duration_minutes' => null,
        ]);
    }

    /**
     * Set specific providers.
     *
     * @param string[] $providers
     */
    public function withProviders(array $providers): static
    {
        return $this->state(static fn (array $attributes) => [
            'providers' => $providers,
        ]);
    }
}
