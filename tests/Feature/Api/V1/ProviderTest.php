<?php

namespace Tests\Feature\Api\V1;

use App\Models\Provider;
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

        Provider::factory()->count(30)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers');

        $response->assertOk();
        $this->assertEquals(1, $response['meta']['current_page']);
        $this->assertEquals(25, $response['meta']['per_page']);
        $this->assertEquals(30, $response['meta']['total']);
        $this->assertCount(25, $response['data']);
    }

    /**
     * Test filtering by enabled status.
     */
    public function testFilterByEnabledStatus(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->count(3)->create(['enabled' => true]);
        Provider::factory()->count(2)->create(['enabled' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers?enabled=1');

        $response->assertOk();
        $this->assertEquals(3, $response['meta']['total']);
        $this->assertCount(3, $response['data']);
        $this->assertTrue($response['data'][0]['enabled']);
    }

    /**
     * Test sorting by name ascending.
     */
    public function testSortByNameAscending(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->create(['name' => 'Ookla']);
        Provider::factory()->create(['name' => 'Cloudflare']);
        Provider::factory()->create(['name' => 'LibreSpeed']);

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

        Provider::factory()->count(50)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/providers?per_page=10&page=1');

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
        $response = $this->getJson('/api/v1/providers');

        $response->assertUnauthorized();
    }
}
