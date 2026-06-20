<?php

namespace Tests\Feature\Api\V1;

use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderScheduleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can list providers/schedules.
     */
    public function testAuthenticatedUserCanListProviderSchedules(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->create();
        ProviderSchedule::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers/schedules');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'provider_slug',
                        'provider_name',
                        'label',
                        'cron_expression',
                        'enabled',
                        'provider_enabled',
                        'next_run_at',
                        'last_scheduled_at',
                    ],
                ],
            ]);
    }

    /**
     * Test that response includes all schedule records.
     */
    public function testResponseIncludesAllSchedules(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->create();
        ProviderSchedule::factory()->count(5)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers/schedules');

        $response->assertStatus(200);
        $this->assertCount(5, $response['data']);
    }

    /**
     * Test that cron expression is included.
     */
    public function testCronExpressionIsIncluded(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->create();
        $schedule = ProviderSchedule::factory()->create(['cron_expression' => '0 * * * *']);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers/schedules');

        $response->assertStatus(200);
        $this->assertSame('0 * * * *', $response['data'][0]['cron_expression']);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/providers/schedules');

        $response->assertStatus(401);
    }
}
