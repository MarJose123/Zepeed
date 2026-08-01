<?php

namespace Database\Factories;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'           => fake()->words(2, true),
            'url'            => fake()->url(),
            'method'         => 'POST',
            'secret'         => null,
            'headers'        => null,
            'timeout'        => 30,
            'retry_attempts' => 3,
            'verify_ssl'     => true,
            'is_active'      => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function withSecret(): static
    {
        return $this->state(fn (array $attributes) => ['secret' => fake()->sha256()]);
    }
}
