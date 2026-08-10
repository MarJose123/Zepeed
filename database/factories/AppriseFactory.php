<?php

namespace Database\Factories;

use App\Models\Apprise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Apprise>
 */
class AppriseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'       => fake()->words(2, true),
            'url'        => fake()->url(),
            'tags'       => [],
            'username'   => null,
            'password'   => null,
            'timeout'    => 30,
            'verify_ssl' => true,
            'is_active'  => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    /**
     * @param array<int, string> $tags
     */
    public function withTags(array $tags): static
    {
        return $this->state(fn (array $attributes) => ['tags' => $tags]);
    }

    public function withBasicAuth(): static
    {
        return $this->state(fn (array $attributes) => [
            'username' => fake()->userName(),
            'password' => fake()->password(12),
        ]);
    }
}
