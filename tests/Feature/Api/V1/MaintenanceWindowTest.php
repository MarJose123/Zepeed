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
                        'label',
                        'type',
                        'is_active',
                        'providers',
                        'starts_at',
                        'ends_at',
                        'cron_expression',
                        'duration_minutes',
                        'notes',
                        'created_at',
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
     * Test pagination with default per_page of 25.
     */
    public function testPaginationWithDefaultPerPage(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        MaintenanceWindow::factory()->count(30)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules');

        $response->assertOk();
        $this->assertEquals(1, $response['meta']['current_page']);
        $this->assertEquals(25, $response['meta']['per_page']);
        $this->assertEquals(30, $response['meta']['total']);
        $this->assertCount(25, $response['data']);
    }

    /**
     * Test filtering by is_active status.
     */
    public function testFilterByIsActiveStatus(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        MaintenanceWindow::factory()->count(3)->create(['is_active' => true]);
        MaintenanceWindow::factory()->count(2)->create(['is_active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules?is_active=1');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
        $this->assertCount(3, $response['data']);
        $this->assertTrue($response['data'][0]['is_active']);
    }

    /**
     * Test filtering by is_active=false.
     */
    public function testFilterByIsInactiveStatus(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        MaintenanceWindow::factory()->count(3)->create(['is_active' => true]);
        MaintenanceWindow::factory()->count(2)->create(['is_active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules?is_active=0');

        $response->assertOk();
        $this->assertEquals(2, $response['meta']['total']);
        $this->assertCount(2, $response['data']);
        $this->assertFalse($response['data'][0]['is_active']);
    }

    /**
     * Test filtering by starts_at from date.
     */
    public function testFilterByStartsAtFromDate(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $oldDate = now()->subDays(10);
        $recentDate = now()->subDays(2);

        MaintenanceWindow::factory()->oneTime()->create(['starts_at' => $oldDate]);
        MaintenanceWindow::factory()->oneTime()->count(2)->create(['starts_at' => $recentDate]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules?starts_at_from=' . $recentDate->format('Y-m-d'));

        $response->assertOk();
        $this->assertEquals(2, $response['meta']['total']);
        $this->assertCount(2, $response['data']);
    }

    /**
     * Test filtering by starts_at to date.
     */
    public function testFilterByStartsAtToDate(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $oldDate = now()->subDays(10);
        $recentDate = now()->subDays(2);

        MaintenanceWindow::factory()->oneTime()->create(['starts_at' => $oldDate]);
        MaintenanceWindow::factory()->oneTime()->count(2)->create(['starts_at' => $recentDate]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules?starts_at_to=' . $oldDate->format('Y-m-d'));

        $response->assertOk();
        $this->assertEquals(1, $response['meta']['total']);
        $this->assertCount(1, $response['data']);
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

        $window1 = MaintenanceWindow::factory()->oneTime()->create(['starts_at' => $date1]);
        MaintenanceWindow::factory()->oneTime()->create(['starts_at' => $date3]);
        MaintenanceWindow::factory()->oneTime()->create(['starts_at' => $date2]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules?sort[starts_at]=desc');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
        $this->assertEquals($window1->fresh()->starts_at->toIso8601String(), $response['data'][0]['starts_at']);
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

    /**
     * Test that an authenticated user can create a recurring maintenance window.
     */
    public function testAuthenticatedUserCanCreateRecurringWindow(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/maintenance/schedules', [
                'label'            => 'Weekly maintenance',
                'type'             => 'recurring',
                'providers'        => ['all'],
                'cron_expression'  => '0 2 * * *',
                'duration_minutes' => 60,
                'is_active'        => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.label', 'Weekly maintenance')
            ->assertJsonPath('data.type', 'recurring');

        $this->assertDatabaseHas('maintenance_windows', ['label' => 'Weekly maintenance']);
    }

    /**
     * Test that an authenticated user can create a one-time maintenance window.
     */
    public function testAuthenticatedUserCanCreateOneTimeWindow(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/maintenance/schedules', [
                'label'     => 'Server move',
                'type'      => 'one_time',
                'providers' => ['ookla'],
                'starts_at' => now()->addDays(1)->toDateTimeString(),
                'ends_at'   => now()->addDays(2)->toDateTimeString(),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'one_time');

        $this->assertDatabaseHas('maintenance_windows', ['label' => 'Server move']);
    }

    /**
     * Test that overlapping one-time windows are rejected.
     */
    public function testOverlappingOneTimeWindowIsRejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        MaintenanceWindow::factory()->oneTime()->create([
            'is_active' => true,
            'providers' => ['ookla'],
            'starts_at' => now()->addDays(1)->toDateTimeString(),
            'ends_at'   => now()->addDays(2)->toDateTimeString(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/maintenance/schedules', [
                'label'     => 'Overlapping window',
                'type'      => 'one_time',
                'providers' => ['ookla'],
                'starts_at' => now()->addDays(1)->addHour()->toDateTimeString(),
                'ends_at'   => now()->addDays(2)->addHour()->toDateTimeString(),
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['starts_at']]);
    }

    /**
     * Test that an authenticated user can update a maintenance window.
     */
    public function testAuthenticatedUserCanUpdateWindow(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $window = MaintenanceWindow::factory()->indefinite()->create(['label' => 'Old label']);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->patchJson("/api/v1/maintenance/schedules/{$window->id}", [
                'label'     => 'New label',
                'type'      => 'indefinite',
                'providers' => ['all'],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.label', 'New label');

        $this->assertDatabaseHas('maintenance_windows', ['id' => $window->id, 'label' => 'New label']);
    }

    /**
     * Test that an authenticated user can update a one-time window without
     * tripping the self-overlap check.
     */
    public function testAuthenticatedUserCanUpdateOneTimeWindow(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $window = MaintenanceWindow::factory()->oneTime()->create([
            'is_active' => true,
            'providers' => ['all'],
            'starts_at' => now()->addDays(1)->toDateTimeString(),
            'ends_at'   => now()->addDays(2)->toDateTimeString(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->patchJson("/api/v1/maintenance/schedules/{$window->id}", [
                'label'     => 'Rescheduled window',
                'type'      => 'one_time',
                'providers' => ['all'],
                'starts_at' => now()->addDays(3)->toDateTimeString(),
                'ends_at'   => now()->addDays(4)->toDateTimeString(),
            ]);

        $response->assertOk()
            ->assertJsonPath('data.label', 'Rescheduled window');
    }

    /**
     * Test that an authenticated user can delete a maintenance window.
     */
    public function testAuthenticatedUserCanDeleteWindow(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $window = MaintenanceWindow::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->deleteJson("/api/v1/maintenance/schedules/{$window->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('maintenance_windows', ['id' => $window->id]);
    }

    /**
     * Test that the global pause can be toggled on and off.
     */
    public function testGlobalPauseCanBeToggled(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/maintenance/global-pause');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('maintenance_windows', [
            'type'      => 'indefinite',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/maintenance/global-pause');

        $response->assertOk();
        $this->assertDatabaseHas('maintenance_windows', [
            'type'      => 'indefinite',
            'is_active' => false,
        ]);
    }

    /**
     * Test that a missing maintenance window returns 404.
     */
    public function testMissingWindowReturns404(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->deleteJson('/api/v1/maintenance/schedules/nonexistent-uuid');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
