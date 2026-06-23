<?php

namespace Tests\Feature\Api\V1;

use App\Models\MaintenanceWindow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceWindowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can list maintenance windows.
     */
    public function testAuthenticatedUserCanListWindows(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        MaintenanceWindow::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'code',
                'data' => [
                    '*' => [
                        'id',
                        'is_active',
                        'providers',
                        'type',
                        'starts_at',
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

        MaintenanceWindow::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules');

        $response->assertOk();
        $this->assertTrue($response['success']);
        $this->assertEquals(200, $response['code']);
    }

    /**
     * Test filtering by is_global status.
     */
    public function testFilterByIsGlobalStatus(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        MaintenanceWindow::factory()->count(3)->create(['is_global' => true]);
        MaintenanceWindow::factory()->count(2)->create(['is_global' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules?is_global=1');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
        $this->assertCount(3, $response['data']);
        $this->assertTrue($response['data'][0]['is_global']);
    }

    /**
     * Test filtering by date range (from).
     */
    public function testFilterByStartsAtFromDate(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $oldDate = now()->subDays(10);
        $recentDate = now()->subDays(2);

        MaintenanceWindow::factory()->create(['starts_at' => $oldDate]);
        MaintenanceWindow::factory()->count(2)->create(['starts_at' => $recentDate]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules?starts_at_from=' . $recentDate->format('Y-m-d'));

        $response->assertOk();
        $this->assertEquals(2, $response['meta']['total']);
        $this->assertCount(2, $response['data']);
    }

    /**
     * Test sorting by starts_at descending.
     */
    public function testSortByStartsAtDescending(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $date1 = now()->subDays(5);
        $date2 = now()->subDays(10);
        $date3 = now()->subDays(15);

        MaintenanceWindow::factory()->create(['starts_at' => $date1]);
        MaintenanceWindow::factory()->create(['starts_at' => $date3]);
        MaintenanceWindow::factory()->create(['starts_at' => $date2]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules?sort[starts_at]=desc');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
        $this->assertEquals($date1->toIso8601String(), $response['data'][0]['starts_at']);
    }

    /**
     * Test response includes pagination links.
     */
    public function testResponseIncludesPaginationLinks(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        MaintenanceWindow::factory()->count(50)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules?per_page=10&page=1');

        $response->assertOk();
        $this->assertNotNull($response['links']['first']);
        $this->assertNotNull($response['links']['last']);
        $this->assertNotNull($response['links']['next']);
        $this->assertNull($response['links']['prev']);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/maintenance/schedules');

        $response->assertUnauthorized();
    }
}
