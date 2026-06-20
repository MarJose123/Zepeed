<?php

namespace Database\Factories;

use App\Enums\SpeedtestServer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SpeedResult>
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
        $status = fake()->randomElement(['success', 'failed', 'skipped']);

        return [
            'provider_slug'   => fake()->randomElement(SpeedtestServer::cases()),
            'status'          => $status,
            'download_mbps'   => $status === 'success' ? fake()->randomFloat(2, 50, 500) : null,
            'upload_mbps'     => $status === 'success' ? fake()->randomFloat(2, 25, 250) : null,
            'ping_ms'         => $status === 'success' ? fake()->randomFloat(2, 5, 100) : null,
            'jitter_ms'       => $status === 'success' ? fake()->randomFloat(2, 0, 50) : null,
            'packet_loss'     => $status === 'success' ? 0.0 : null,
            'download_bytes'  => $status === 'success' ? fake()->numberBetween(1000000, 10000000) : null,
            'upload_bytes'    => $status === 'success' ? fake()->numberBetween(500000, 5000000) : null,
            'server_name'     => $status === 'success' ? fake()->city() : null,
            'server_location' => $status === 'success' ? fake()->state() : null,
            'isp'             => fake()->word(),
            'share_url'       => $status === 'success' ? fake()->url() : null,
            'client_ip'       => fake()->ipv4(),
            'failure_reason'  => $status === 'failed' ? fake()->word() : null,
            'failure_message' => $status === 'failed' ? fake()->sentence() : null,
            'raw_json'        => null,
            'measured_at'     => fake()->dateTimeThisMonth(),
        ];
    }

    /**
     * Indicate a successful speed test result.
     */
    public function success(): static
    {
        return $this->state(static fn (array $attributes) => [
            'status'          => 'success',
            'download_mbps'   => fake()->randomFloat(2, 100, 500),
            'upload_mbps'     => fake()->randomFloat(2, 50, 250),
            'ping_ms'         => fake()->randomFloat(2, 5, 50),
            'jitter_ms'       => fake()->randomFloat(2, 0, 20),
            'packet_loss'     => 0.0,
            'download_bytes'  => fake()->numberBetween(5000000, 10000000),
            'upload_bytes'    => fake()->numberBetween(2500000, 5000000),
            'server_name'     => fake()->city(),
            'server_location' => fake()->state(),
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
        return $this->state(static fn (array $attributes) => [
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
            'failure_reason'  => 'Connection timeout',
            'failure_message' => 'Unable to connect to speedtest server',
        ]);
    }

    /**
     * Indicate a skipped speed test result.
     */
    public function skipped(): static
    {
        return $this->state(static fn (array $attributes) => [
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
