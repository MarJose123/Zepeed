<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for the creation-time Apprise connection check
 * (POST /speedtest/integration/apprise/test-config).
 */
class AppriseWebTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a valid configuration passes the connection check without
     * persisting anything.
     */
    public function testCreationConnectionCheckSucceedsWithoutPersisting(): void
    {
        $user = User::factory()->create();

        Http::fake();

        $response = $this->actingAs($user)
            ->postJson('/speedtest/integration/apprise/test-config', [
                'name'       => 'Draft instance',
                'url'        => 'https://apprise.example.com/notify',
                'tags'       => ['production', 'critical'],
                'username'   => null,
                'password'   => null,
                'timeout'    => 15,
                'verify_ssl' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://apprise.example.com/notify'
            && $request['title'] === 'Zepeed test notification'
            && $request['tag'] === 'production,critical');

        $this->assertDatabaseCount('apprises', 0);
    }

    /**
     * Test that a failed connection check returns a sanitized error message
     * and persists nothing.
     */
    public function testCreationConnectionCheckReportsFailure(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://apprise.example.com/*' => Http::response(null, 503),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/speedtest/integration/apprise/test-config', [
                'name' => 'Draft instance',
                'url'  => 'https://apprise.example.com/notify',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);

        $this->assertStringContainsString('HTTP status 503', $response['message']);
        $this->assertDatabaseCount('apprises', 0);
    }

    /**
     * Test that the connection check validates the configuration payload.
     */
    public function testCreationConnectionCheckValidatesPayload(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/speedtest/integration/apprise/test-config', [
                'name' => 'Missing URL',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);

        $this->assertDatabaseCount('apprises', 0);
    }
}
