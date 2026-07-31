<?php

namespace Tests\Feature\Api\V1;

use App\Enums\SpeedtestServer;
use App\Models\Provider;
use App\Models\SpeedResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated user can list providers.
     */
    public function testAuthenticatedUserCanListProviders(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'code',
                'data' => [
                    '*' => [
                        'id',
                        'name',
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

        Provider::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers');

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

        Provider::factory()->count(4)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers');

        $response->assertOk();
        $this->assertEquals(1, $response['meta']['current_page']);
        $this->assertEquals(25, $response['meta']['per_page']);
        $this->assertEquals(4, $response['meta']['total']);
        $this->assertCount(4, $response['data']);
    }

    /**
     * Test filtering by enabled status.
     */
    public function testFilterByEnabledStatus(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->count(2)->create(['is_enabled' => true]);
        Provider::factory()->count(2)->create(['is_enabled' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers?enabled=1');

        $response->assertOk();
        $this->assertEquals(2, $response['meta']['total']);
        $this->assertCount(2, $response['data']);
        $this->assertTrue($response['data'][0]['enabled']);
    }

    /**
     * Test sorting by name ascending.
     */
    public function testSortByNameAscending(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->withSlug(SpeedtestServer::Ookla)->create();
        Provider::factory()->withSlug(SpeedtestServer::Cloudflare)->create();
        Provider::factory()->withSlug(SpeedtestServer::Librespeed)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers?sort[name]=asc');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
        $this->assertEquals('Cloudflare', $response['data'][0]['name']);
        $this->assertEquals('LibreSpeed', $response['data'][1]['name']);
        $this->assertEquals('Ookla', $response['data'][2]['name']);
    }

    /**
     * Test response includes pagination links.
     */
    public function testResponseIncludesPaginationLinks(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->count(4)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers?per_page=10&page=1');

        $response->assertOk();
        $this->assertNotNull($response['links']['first']);
        $this->assertNotNull($response['links']['last']);
        $this->assertNull($response['links']['next']);
        $this->assertNull($response['links']['prev']);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/providers');

        $response->assertUnauthorized();
    }

    /**
     * Test provider exposes the last result and the last known good result
     * when the most recent scheduled run failed.
     */
    public function testProviderIncludesLastResultAndLastKnownGood(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->withSlug(SpeedtestServer::Ookla)->create();

        SpeedResult::factory()->success()->create([
            'provider_slug' => SpeedtestServer::Ookla,
            'measured_at'   => now()->subDays(2),
            'download_mbps' => 120,
        ]);
        SpeedResult::factory()->failed()->create([
            'provider_slug' => SpeedtestServer::Ookla,
            'measured_at'   => now()->subHour(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers');

        $response->assertOk();
        $providerData = collect($response['data'])->firstWhere('slug', 'ookla');

        $this->assertSame('failed', $providerData['last_result']['status']);
        $this->assertSame('success', $providerData['last_known_good']['status']);
        $this->assertEquals(120.0, $providerData['last_known_good']['download']);
    }

    /**
     * Test provider last_known_good is null when no successful result exists.
     */
    public function testProviderLastKnownGoodIsNullWithoutSuccessfulResult(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->withSlug(SpeedtestServer::Ookla)->create();

        SpeedResult::factory()->failed()->create([
            'provider_slug' => SpeedtestServer::Ookla,
            'measured_at'   => now()->subHour(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers');

        $response->assertOk();
        $providerData = collect($response['data'])->firstWhere('slug', 'ookla');

        $this->assertSame('failed', $providerData['last_result']['status']);
        $this->assertNull($providerData['last_known_good']);
    }

    /**
     * Test provider last_known_good equals the last result when the latest
     * scheduled run succeeded.
     */
    public function testProviderLastKnownGoodEqualsLastResultWhenLatestRunSucceeded(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->withSlug(SpeedtestServer::Ookla)->create();

        $latest = SpeedResult::factory()->success()->create([
            'provider_slug' => SpeedtestServer::Ookla,
            'measured_at'   => now()->subMinutes(30),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers');

        $response->assertOk();
        $providerData = collect($response['data'])->firstWhere('slug', 'ookla');

        $this->assertSame('success', $providerData['last_result']['status']);
        $this->assertSame($latest->id, $providerData['last_known_good']['id']);
    }

    /**
     * Test provider last_result and last_known_good are null when no results exist.
     */
    public function testProviderLastResultAndLastKnownGoodAreNullWithoutResults(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->withSlug(SpeedtestServer::Ookla)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers');

        $response->assertOk();
        $providerData = collect($response['data'])->firstWhere('slug', 'ookla');

        $this->assertNull($providerData['last_result']);
        $this->assertNull($providerData['last_known_good']);
    }

    /**
     * Test provider last_result and last_known_good resolve deterministically
     * when multiple results share the same measured_at timestamp.
     */
    public function testProviderLastResultAndLastKnownGoodBreakTimestampTies(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->withSlug(SpeedtestServer::Ookla)->create();

        $sameTimestamp = now()->subHour();

        SpeedResult::factory()->success()->create([
            'provider_slug' => SpeedtestServer::Ookla,
            'measured_at'   => $sameTimestamp,
            'download_mbps' => 100,
        ]);
        $newest = SpeedResult::factory()->success()->create([
            'provider_slug' => SpeedtestServer::Ookla,
            'measured_at'   => $sameTimestamp,
            'download_mbps' => 200,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers');

        $response->assertOk();
        $providerData = collect($response['data'])->firstWhere('slug', 'ookla');

        $this->assertSame($newest->id, $providerData['last_result']['id']);
        $this->assertSame($newest->id, $providerData['last_known_good']['id']);
    }
}
