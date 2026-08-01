<?php

namespace Tests\Feature\Api\V1;

use App\Models\ProviderSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderScheduleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can list provider schedules.
     */
    public function testAuthenticatedUserCanListSchedules(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        ProviderSchedule::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers/schedules');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'code',
                'data' => [
                    '*' => [
                        'id',
                        'provider_slug',
                        'enabled',
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

        ProviderSchedule::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers/schedules');

        $response->assertOk();
        $this->assertTrue($response['success']);
        $this->assertEquals(200, $response['code']);
    }

    /**
     * Test filtering by enabled status.
     */
    public function testFilterByEnabledStatus(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        ProviderSchedule::factory()->count(3)->create(['is_enabled' => true]);
        ProviderSchedule::factory()->count(2)->create(['is_enabled' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers/schedules?enabled=0');

        $response->assertOk();
        $this->assertEquals(2, $response['meta']['total']);
        $this->assertCount(2, $response['data']);
        $this->assertFalse($response['data'][0]['enabled']);
    }

    /**
     * Test sorting by created_at descending.
     */
    public function testSortByCreatedAtDescending(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        ProviderSchedule::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers/schedules?sort[created_at]=desc');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/providers/schedules');

        $response->assertUnauthorized();
    }

    /**
     * Test that an authenticated user can create a provider schedule.
     */
    public function testAuthenticatedUserCanCreateSchedule(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/providers/schedules', [
                'provider_slug'   => 'ookla',
                'label'           => 'Hourly speedtest',
                'cron_expression' => '0 * * * *',
                'is_enabled'      => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 201)
            ->assertJsonPath('data.provider_slug', 'ookla')
            ->assertJsonPath('data.label', 'Hourly speedtest')
            ->assertJsonPath('data.enabled', true);

        $this->assertDatabaseHas('provider_schedules', [
            'provider_slug'   => 'ookla',
            'label'           => 'Hourly speedtest',
            'cron_expression' => '0 * * * *',
            'is_enabled'      => true,
        ]);
    }

    /**
     * Test that schedule creation validates the provider slug against
     * the known provider enum.
     */
    public function testScheduleCreationValidatesProviderSlug(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/providers/schedules', [
                'provider_slug'   => 'not-a-provider',
                'label'           => 'Bad schedule',
                'cron_expression' => '0 * * * *',
                'is_enabled'      => true,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['provider_slug']]);

        $this->assertDatabaseMissing('provider_schedules', ['label' => 'Bad schedule']);
    }

    /**
     * Test that schedule creation rejects an invalid cron expression.
     */
    public function testScheduleCreationRejectsInvalidCron(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/providers/schedules', [
                'provider_slug'   => 'ookla',
                'label'           => 'Bad cron',
                'cron_expression' => 'not a cron',
                'is_enabled'      => true,
            ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['cron_expression']]);
    }

    /**
     * Test that an authenticated user can view a single provider schedule.
     */
    public function testAuthenticatedUserCanViewSchedule(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $schedule = ProviderSchedule::factory()->create([
            'label'         => 'Nightly run',
            'provider_slug' => 'cloudflare',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/providers/schedules/{$schedule->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $schedule->id)
            ->assertJsonPath('data.label', 'Nightly run')
            ->assertJsonPath('data.provider_slug', 'cloudflare');
    }

    /**
     * Test that an authenticated user can update a provider schedule.
     */
    public function testAuthenticatedUserCanUpdateSchedule(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $schedule = ProviderSchedule::factory()->create([
            'label'      => 'Old label',
            'is_enabled' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->patchJson("/api/v1/providers/schedules/{$schedule->id}", [
                'label'      => 'New label',
                'is_enabled' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.label', 'New label')
            ->assertJsonPath('data.enabled', false);

        $this->assertDatabaseHas('provider_schedules', [
            'id'         => $schedule->id,
            'label'      => 'New label',
            'is_enabled' => false,
        ]);
    }

    /**
     * Test that an authenticated user can delete a provider schedule.
     */
    public function testAuthenticatedUserCanDeleteSchedule(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $schedule = ProviderSchedule::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->deleteJson("/api/v1/providers/schedules/{$schedule->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('provider_schedules', ['id' => $schedule->id]);
    }

    /**
     * Test that a missing provider schedule returns 404.
     */
    public function testMissingScheduleReturns404(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers/schedules/nonexistent-uuid');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
