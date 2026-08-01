<?php

namespace Database\Factories;

use App\Enums\ExportFormat;
use App\Enums\ExportModule;
use App\Enums\ExportStatus;
use App\Models\ExportRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExportRequest>
 */
class ExportRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'module'  => ExportModule::SpeedDownload,
            'format'  => ExportFormat::Csv,
            'status'  => ExportStatus::Pending,
            'filters' => [
                'provider'  => null,
                'date_from' => now()->subMonth()->format('Y-m-d'),
                'date_to'   => now()->format('Y-m-d'),
            ],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'     => ExportStatus::Completed,
            'file_path'  => 'exports/' . fake()->uuid() . '.csv',
            'row_count'  => fake()->numberBetween(1, 500),
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'          => ExportStatus::Failed,
            'failure_message' => 'Export generation failed.',
        ]);
    }
}
