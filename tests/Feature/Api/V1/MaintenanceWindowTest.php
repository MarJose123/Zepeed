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
    public function testAuthenticatedUserCanListMaintenanceWindows(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        MaintenanceWindow::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'label',
                        'type',
                        'type_label',
                        'is_active',
                        'providers',
                        'covers_all',
                        'starts_at',
                        'ends_at',
                        'cron_expression',
                        'duration_minutes',
                        'notes',
                        'is_currently_active',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * Test that response includes all maintenance records.
     */
    public function testResponseIncludesAllMaintenanceWindows(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        MaintenanceWindow::factory()->count(5)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules');

        $response->assertStatus(200);
        $this->assertCount(5, $response['data']);
    }

    /**
     * Test that active status is correctly represented.
     */
    public function testActiveStatusIsCorrect(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        MaintenanceWindow::factory()->create(['is_active' => true]);
        MaintenanceWindow::factory()->create(['is_active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/maintenance/schedules');

        $response->assertStatus(200);

        $active = collect($response['data'])->where('is_active', true)->count();
        $inactive = collect($response['data'])->where('is_active', false)->count();

        $this->assertEqual(1, $active);
        $this->assertEqual(1, $inactive);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/maintenance/schedules');

        $response->assertStatus(401);
    }
}
