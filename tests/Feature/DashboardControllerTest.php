<?php

namespace Tests\Feature;

use App\Enums\SpeedtestServer;
use App\Models\SpeedResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testSpeedSeriesBucketsByExactTimestampAndRoundsToTwoDecimals(): void
    {
        $user = User::factory()->create();
        $measuredAt = now()->subHours(2)->seconds(0)->microseconds(0);

        SpeedResult::factory()->create([
            'provider_slug' => SpeedtestServer::Ookla,
            'status'        => 'success',
            'download_mbps' => 123.456,
            'upload_mbps'   => 45.678,
            'measured_at'   => $measuredAt,
        ]);

        $response = $this->actingAs($user)->get('/dashboard?range=1d');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('chartSeries.0.ookla_dl', 123.46)
            ->where('chartSeries.0.ookla_ul', 45.68)
            ->has('chartSeries.0.label'));
    }

    public function testSpeedSeriesUsesDateOnlyLabelForWideRanges(): void
    {
        $user = User::factory()->create();
        $measuredAt = now()->subDays(5);

        SpeedResult::factory()->create([
            'provider_slug' => SpeedtestServer::Librespeed,
            'status'        => 'success',
            'measured_at'   => $measuredAt,
        ]);

        $response = $this->actingAs($user)->get('/dashboard?range=7d');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('chartSeries.0.label', $measuredAt->format('m/d')));
    }

    public function testChartAveragesReturnZeroForProviderWithNoResultsInRange(): void
    {
        $user = User::factory()->create();

        SpeedResult::factory()->create([
            'provider_slug' => SpeedtestServer::Netflix,
            'status'        => 'success',
            'measured_at'   => now()->subDays(60), // outside default 1d window
        ]);

        $response = $this->actingAs($user)->get('/dashboard?range=1d');

        $response->assertOk();
        // No active providers in the 1-day window, so chartAverages should be empty.
        $response->assertInertia(fn ($page) => $page->where('chartAverages', []));
    }

    public function testFailedResultsAreExcludedFromSpeedSeries(): void
    {
        $user = User::factory()->create();

        SpeedResult::factory()->failed()->create([
            'provider_slug' => SpeedtestServer::Cloudflare,
            'measured_at'   => now()->subHours(1),
        ]);

        $response = $this->actingAs($user)->get('/dashboard?range=1d');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('chartSeries', []));
    }
}
