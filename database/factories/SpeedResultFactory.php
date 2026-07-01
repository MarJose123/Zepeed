<?php

namespace Database\Factories;

use App\Enums\SpeedtestServer;
use App\Models\SpeedResult;
use App\Services\Speedtest\Exceptions\SpeedtestFailureReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpeedResult>
 */
class SpeedResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_slug'   => fake()->randomElement(SpeedtestServer::cases()),
            'status'          => 'success',
            'download_mbps'   => fake()->randomFloat(2, 50, 500),
            'upload_mbps'     => fake()->randomFloat(2, 25, 250),
            'ping_ms'         => fake()->randomFloat(2, 5, 100),
            'jitter_ms'       => fake()->randomFloat(2, 0, 50),
            'packet_loss'     => 0.0,
            'download_bytes'  => fake()->numberBetween(1000000, 10000000),
            'upload_bytes'    => fake()->numberBetween(500000, 5000000),
            'server_name'     => fake()->city(),
            'server_location' => fake()->country(),
            'isp'             => fake()->company(),
            'share_url'       => fake()->url(),
            'client_ip'       => fake()->ipv4(),
            'failure_reason'  => null,
            'failure_message' => null,
            'raw_json'        => null,
            'measured_at'     => fake()->dateTimeThisMonth(),
        ];
    }

    /**
     * Indicate a successful speed test result.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'          => 'success',
            'download_mbps'   => fake()->randomFloat(2, 100, 500),
            'upload_mbps'     => fake()->randomFloat(2, 50, 250),
            'ping_ms'         => fake()->randomFloat(2, 5, 50),
            'jitter_ms'       => fake()->randomFloat(2, 0, 20),
            'packet_loss'     => 0.0,
            'server_name'     => fake()->city(),
            'server_location' => fake()->country(),
            'share_url'       => fake()->url(),
            'failure_reason'  => null,
            'failure_message' => null,
        ]);
    }

    /**
     * Indicate a failed speed test result.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'          => 'failed',
            'download_mbps'   => null,
            'upload_mbps'     => null,
            'ping_ms'         => null,
            'jitter_ms'       => null,
            'packet_loss'     => null,
            'download_bytes'  => null,
            'upload_bytes'    => null,
            'server_name'     => null,
            'server_location' => null,
            'share_url'       => null,
            'failure_reason'  => fake()->randomElement(SpeedtestFailureReason::cases()),
            'failure_message' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate a skipped speed test result.
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'          => 'skipped',
            'download_mbps'   => null,
            'upload_mbps'     => null,
            'ping_ms'         => null,
            'jitter_ms'       => null,
            'packet_loss'     => null,
            'download_bytes'  => null,
            'upload_bytes'    => null,
            'server_name'     => null,
            'server_location' => null,
            'share_url'       => null,
            'failure_reason'  => null,
            'failure_message' => null,
        ]);
    }
}
