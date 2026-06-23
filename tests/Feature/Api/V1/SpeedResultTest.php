<?php

namespace Tests\Feature\Api\V1;

use App\Enums\SpeedtestServer;
use App\Models\SpeedResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpeedResultTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can list speedtest results.
     */
    public function testAuthenticatedUserCanListSpeedResults(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'code',
                'data' => [
                    '*' => [
                        'id',
                        'provider_slug',
                        'status',
                        'download',
                        'upload',
                        'ping',
                        'jitter',
                        'packet_loss',
                        'server_name',
                        'server_location',
                        'isp',
                        'share_url',
                        'measured_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'to',
                    'per_page',
                    'total',
                    'last_page',
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
            ]);
    }

    /**
     * Test successful response structure with success and code fields.
     */
    public function testResponseIncludesSuccessAndCodeFields(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results');

        $response->assertOk();
        $this->assertTrue($response['success']);
        $this->assertEquals(200, $response['code']);
    }

    /**
     * Test pagination with default per_page of 25.
     */
    public function testPaginationWithDefaultPerPage(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->count(30)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results');

        $response->assertOk();
        $this->assertEquals(1, $response['meta']['current_page']);
        $this->assertEquals(25, $response['meta']['per_page']);
        $this->assertEquals(30, $response['meta']['total']);
        $this->assertEquals(2, $response['meta']['last_page']);
        $this->assertCount(25, $response['data']);
    }

    /**
     * Test pagination with custom per_page parameter.
     */
    public function testPaginationWithCustomPerPage(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->count(50)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results?per_page=10');

        $response->assertOk();
        $this->assertEquals(10, $response['meta']['per_page']);
        $this->assertCount(10, $response['data']);
        $this->assertEquals(5, $response['meta']['last_page']);
    }

    /**
     * Test per_page maximum limit of 100.
     */
    public function testPerPageMaximumLimitOfOneHundred(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->count(150)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results?per_page=500');

        $response->assertOk();
        $this->assertEquals(100, $response['meta']['per_page']);
        $this->assertCount(100, $response['data']);
    }

    /**
     * Test pagination to second page.
     */
    public function testPaginationToSecondPage(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->count(30)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results?page=2&per_page=10');

        $response->assertOk();
        $this->assertEquals(2, $response['meta']['current_page']);
        $this->assertEquals(11, $response['meta']['from']);
        $this->assertEquals(20, $response['meta']['to']);
        $this->assertCount(10, $response['data']);
    }

    /**
     * Test filtering by provider slug.
     */
    public function testFilterByProviderSlug(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->count(3)->create(['provider_slug' => SpeedtestServer::Ookla]);
        SpeedResult::factory()->count(2)->create(['provider_slug' => SpeedtestServer::Librespeed]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results?provider_slug=ookla');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
        $this->assertCount(3, $response['data']);
        $this->assertEquals('ookla', $response['data'][0]['provider_slug']);
    }

    /**
     * Test filtering by date range (from date).
     */
    public function testFilterByMeasuredAtFromDate(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $oldDate = now()->subDays(10);
        $recentDate = now()->subDays(2);

        SpeedResult::factory()->create(['measured_at' => $oldDate]);
        SpeedResult::factory()->count(2)->create(['measured_at' => $recentDate]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results?measured_at_from=' . $recentDate->format('Y-m-d'));

        $response->assertOk();
        $this->assertEquals(2, $response['meta']['total']);
        $this->assertCount(2, $response['data']);
    }

    /**
     * Test filtering by date range (to date).
     */
    public function testFilterByMeasuredAtToDate(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $oldDate = now()->subDays(10);
        $recentDate = now()->subDays(2);

        SpeedResult::factory()->create(['measured_at' => $oldDate]);
        SpeedResult::factory()->count(2)->create(['measured_at' => $recentDate]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results?measured_at_to=' . $oldDate->format('Y-m-d'));

        $response->assertOk();
        $this->assertEquals(1, $response['meta']['total']);
        $this->assertCount(1, $response['data']);
    }

    /**
     * Test filtering by date range (both from and to).
     */
    public function testFilterByMeasuredAtDateRange(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $date1 = now()->subDays(20);
        $date2 = now()->subDays(10);
        $date3 = now()->subDays(5);

        SpeedResult::factory()->create(['measured_at' => $date1]);
        SpeedResult::factory()->count(2)->create(['measured_at' => $date2]);
        SpeedResult::factory()->create(['measured_at' => $date3]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results?measured_at_from=' . $date2->format('Y-m-d') . '&measured_at_to=' . $date3->format('Y-m-d'));

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
    }

    /**
     * Test sorting by measured_at descending (default).
     */
    public function testSortByMeasuredAtDescending(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $date1 = now()->subDays(5);
        $date2 = now()->subDays(10);
        $date3 = now()->subDays(15);

        SpeedResult::factory()->create(['measured_at' => $date1]);
        SpeedResult::factory()->create(['measured_at' => $date3]);
        SpeedResult::factory()->create(['measured_at' => $date2]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results?sort[measured_at]=desc');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
        // Most recent first
        $this->assertEquals($date1->toIso8601String(), $response['data'][0]['measured_at']);
    }

    /**
     * Test sorting by download_mbps.
     */
    public function testSortByDownloadMbps(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->create(['download_mbps' => 100]);
        SpeedResult::factory()->create(['download_mbps' => 50]);
        SpeedResult::factory()->create(['download_mbps' => 150]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results?sort[download_mbps]=asc');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
        $this->assertEquals(50, $response['data'][0]['download']);
        $this->assertEquals(100, $response['data'][1]['download']);
        $this->assertEquals(150, $response['data'][2]['download']);
    }

    /**
     * Test searching by server name.
     */
    public function testSearchByServerName(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->create(['server_name' => 'New York Server']);
        SpeedResult::factory()->create(['server_name' => 'Los Angeles Server']);
        SpeedResult::factory()->create(['server_name' => 'London Server']);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results?search=New%20York');

        $response->assertOk();
        $this->assertEquals(1, $response['meta']['total']);
        $this->assertEquals('New York Server', $response['data'][0]['server_name']);
    }

    /**
     * Test combined filter and sorting.
     */
    public function testCombinedFilterAndSorting(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->count(2)->create([
            'provider_slug' => SpeedtestServer::Ookla,
            'download_mbps' => 100,
        ]);
        SpeedResult::factory()->count(2)->create([
            'provider_slug' => SpeedtestServer::Ookla,
            'download_mbps' => 150,
        ]);
        SpeedResult::factory()->count(2)->create(['provider_slug' => SpeedtestServer::Librespeed]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results?provider_slug=ookla&sort[download_mbps]=desc');

        $response->assertOk();
        $this->assertEquals(4, $response['meta']['total']);
        $this->assertEquals(150, $response['data'][0]['download']);
    }

    /**
     * Test response includes pagination links.
     */
    public function testResponseIncludesPaginationLinks(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->count(50)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results?per_page=10&page=1');

        $response->assertOk();
        $this->assertNotNull($response['links']['first']);
        $this->assertNotNull($response['links']['last']);
        $this->assertNotNull($response['links']['next']);
        $this->assertNull($response['links']['prev']);
    }

    /**
     * Test speed metrics are correctly formatted.
     */
    public function testSpeedMetricsAreCorrectlyFormatted(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        SpeedResult::factory()->create([
            'download_mbps' => '150.5678',
            'upload_mbps'   => '75.1234',
            'ping_ms'       => '25.9999',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/speedtest/results');

        $response->assertOk();
        $this->assertEquals(150.57, $response['data'][0]['download']);
        $this->assertEquals(75.12, $response['data'][0]['upload']);
        $this->assertEquals(26.0, $response['data'][0]['ping']);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/speedtest/results');

        $response->assertUnauthorized();
    }
}
