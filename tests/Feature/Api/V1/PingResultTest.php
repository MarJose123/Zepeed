<?php

namespace Tests\Feature\Api\V1;

use App\Enums\PingResultStatus;
use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PingResultTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can list ping results.
     */
    public function testAuthenticatedUserCanListPingResults(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();
        PingResult::factory()->count(3)->for($target, 'target')->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'code',
                'data' => [
                    '*' => [
                        'id',
                        'ping_target_id',
                        'status',
                        'target' => [
                            'label',
                            'host',
                        ],
                        'packets_sent',
                        'packets_received',
                        'packet_loss_percent',
                        'min_ms',
                        'avg_ms',
                        'max_ms',
                        'stddev_ms',
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

        $target = PingTarget::factory()->create();
        PingResult::factory()->count(2)->for($target, 'target')->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings');

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

        $target = PingTarget::factory()->create();
        PingResult::factory()->count(30)->for($target, 'target')->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings');

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

        $target = PingTarget::factory()->create();
        PingResult::factory()->count(50)->for($target, 'target')->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings?per_page=15');

        $response->assertOk();
        $this->assertEquals(15, $response['meta']['per_page']);
        $this->assertCount(15, $response['data']);
        $this->assertEquals(4, $response['meta']['last_page']);
    }

    /**
     * Test per_page maximum limit of 100.
     */
    public function testPerPageMaximumLimitOfOneHundred(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();
        PingResult::factory()->count(150)->for($target, 'target')->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings?per_page=200');

        $response->assertOk();
        $this->assertEquals(100, $response['meta']['per_page']);
        $this->assertCount(100, $response['data']);
    }

    /**
     * Test filtering by ping_target_id via target_id query param.
     */
    public function testFilterByTargetId(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target1 = PingTarget::factory()->create();
        $target2 = PingTarget::factory()->create();

        PingResult::factory()->count(3)->for($target1, 'target')->create();
        PingResult::factory()->count(2)->for($target2, 'target')->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/pings?target_id={$target1->id}");

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
        $this->assertCount(3, $response['data']);
        $this->assertEquals($target1->id, $response['data'][0]['ping_target_id']);
    }

    /**
     * Test filtering by status.
     */
    public function testFilterByStatus(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();
        PingResult::factory()->count(3)->for($target, 'target')->create([
            'status' => PingResultStatus::Success,
        ]);
        PingResult::factory()->count(2)->for($target, 'target')->create([
            'status' => PingResultStatus::Failed,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings?status=success');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
        $this->assertCount(3, $response['data']);
    }

    /**
     * Test filtering by measured_at from date.
     */
    public function testFilterByMeasuredAtFromDate(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();
        $oldDate = now()->subDays(10);
        $recentDate = now()->subDays(2);

        PingResult::factory()->for($target, 'target')->create(['measured_at' => $oldDate]);
        PingResult::factory()->count(2)->for($target, 'target')->create(['measured_at' => $recentDate]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings?measured_at_from=' . $recentDate->format('Y-m-d'));

        $response->assertOk();
        $this->assertEquals(2, $response['meta']['total']);
        $this->assertCount(2, $response['data']);
    }

    /**
     * Test filtering by measured_at to date.
     */
    public function testFilterByMeasuredAtToDate(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();
        $oldDate = now()->subDays(10);
        $recentDate = now()->subDays(2);

        PingResult::factory()->for($target, 'target')->create(['measured_at' => $oldDate]);
        PingResult::factory()->count(2)->for($target, 'target')->create(['measured_at' => $recentDate]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings?measured_at_to=' . $oldDate->format('Y-m-d'));

        $response->assertOk();
        $this->assertEquals(1, $response['meta']['total']);
        $this->assertCount(1, $response['data']);
    }

    /**
     * Test sorting by measured_at descending.
     */
    public function testSortByMeasuredAtDescending(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();

        $result1 = PingResult::factory()->for($target, 'target')->create(['measured_at' => now()->subDays(5)]);
        PingResult::factory()->for($target, 'target')->create(['measured_at' => now()->subDays(15)]);
        PingResult::factory()->for($target, 'target')->create(['measured_at' => now()->subDays(10)]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings?sort[measured_at]=desc');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);

        /** @var PingResult $fresh */
        $fresh = $result1->fresh();
        $this->assertEquals($fresh->measured_at->toISOString(), $response['data'][0]['measured_at']);
    }

    /**
     * Test sorting by avg_ms ascending.
     */
    public function testSortByAvgMsAscending(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();
        PingResult::factory()->for($target, 'target')->create(['avg_ms' => 50.5]);
        PingResult::factory()->for($target, 'target')->create(['avg_ms' => 20.3]);
        PingResult::factory()->for($target, 'target')->create(['avg_ms' => 75.8]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings?sort[avg_ms]=asc');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
        $this->assertEquals(20.3, $response['data'][0]['avg_ms']);
        $this->assertEquals(50.5, $response['data'][1]['avg_ms']);
        $this->assertEquals(75.8, $response['data'][2]['avg_ms']);
    }

    /**
     * Test filtering by status combined with sort.
     */
    public function testCombinedFilterAndSorting(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();
        PingResult::factory()->count(2)->for($target, 'target')->create([
            'status' => PingResultStatus::Success,
            'avg_ms' => 25.5,
        ]);
        PingResult::factory()->count(2)->for($target, 'target')->create([
            'status' => PingResultStatus::Success,
            'avg_ms' => 15.3,
        ]);
        PingResult::factory()->count(2)->for($target, 'target')->create([
            'status' => PingResultStatus::Failed,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings?status=success&sort[avg_ms]=desc');

        $response->assertOk();
        $this->assertEquals(4, $response['meta']['total']);
        $this->assertEquals(25.5, $response['data'][0]['avg_ms']);
    }

    /**
     * Test response includes pagination links.
     */
    public function testResponseIncludesPaginationLinks(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();
        PingResult::factory()->count(50)->for($target, 'target')->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings?per_page=10&page=1');

        $response->assertOk();
        $this->assertNotNull($response['links']['first']);
        $this->assertNotNull($response['links']['last']);
        $this->assertNotNull($response['links']['next']);
        $this->assertNull($response['links']['prev']);
    }

    /**
     * Test status and status_label are correctly represented.
     */
    public function testStatusIsCorrectlyRepresented(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();
        PingResult::factory()->for($target, 'target')->create([
            'status' => PingResultStatus::Success,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/pings');

        $response->assertOk();
        $this->assertEquals('success', $response['data'][0]['status']);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/pings');

        $response->assertUnauthorized();
    }
}
