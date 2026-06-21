<?php

namespace Database\Factories;

use App\Enums\PingResultStatus;
use App\Models\PingResult;
use App\Models\PingTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PingResult>
 */
class PingResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $minMs = fake()->randomFloat(2, 5, 50);
        $maxMs = fake()->randomFloat(2, $minMs + 10, 150);
        $avgMs = fake()->randomFloat(2, $minMs, $maxMs);

        return [
            'ping_target_id'      => PingTarget::factory(),
            'status'              => PingResultStatus::Success,
            'packets_sent'        => 4,
            'packets_received'    => 4,
            'packet_loss_percent' => 0.0,
            'min_ms'              => $minMs,
            'avg_ms'              => $avgMs,
            'max_ms'              => $maxMs,
            'stddev_ms'           => fake()->randomFloat(2, 1, 20),
            'raw_output'          => null,
            'failure_reason'      => null,
            'measured_at'         => fake()->dateTimeThisMonth(),
        ];
    }

    /**
     * Indicate a successful ping result.
     */
    public function success(): static
    {
        return $this->state(static fn (array $attributes) => [
            'status'              => PingResultStatus::Success,
            'packets_received'    => 4,
            'packet_loss_percent' => 0.0,
            'failure_reason'      => null,
        ]);
    }

    /**
     * Indicate a failed ping result.
     */
    public function failed(): static
    {
        return $this->state(static fn (array $attributes) => [
            'status'              => PingResultStatus::Failed,
            'packets_received'    => 0,
            'packet_loss_percent' => 100.0,
            'failure_reason'      => 'Timeout',
        ]);
    }
}
