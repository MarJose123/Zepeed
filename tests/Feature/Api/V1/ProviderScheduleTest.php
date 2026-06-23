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
                        'is_enabled',
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
        $this->assertFalse($response['data'][0]['is_enabled']);
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
}
