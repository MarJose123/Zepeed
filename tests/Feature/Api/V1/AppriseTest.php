<?php

namespace Tests\Feature\Api\V1;

use App\Models\Apprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppriseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an authenticated user can list Apprise instances.
     */
    public function testAuthenticatedUserCanListApprises(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Apprise::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/apprise');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'code',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'url',
                        'tags',
                        'has_credentials',
                        'username',
                        'timeout',
                        'verify_ssl',
                        'is_active',
                        'last_fired_at',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
                'links',
            ]);

        $this->assertEquals(3, $response['meta']['total']);
        $this->assertTrue($response['success']);
    }

    /**
     * Test that the stored password is never exposed by the API.
     */
    public function testPasswordIsNeverExposedByTheApi(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->withBasicAuth()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/apprise/{$apprise->id}");

        $response->assertOk()
            ->assertJsonPath('data.has_credentials', true)
            ->assertJsonPath('data.username', $apprise->username)
            ->assertJsonMissingPath('data.password');

        $this->assertStringNotContainsString(
            $apprise->password,
            $response->getContent(),
        );
    }

    /**
     * Test that an authenticated user can create an Apprise instance with
     * tags and Basic Auth credentials.
     */
    public function testAuthenticatedUserCanCreateApprise(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/apprise', [
                'name'       => 'Production notifications',
                'url'        => 'https://apprise.example.com/notify/production',
                'tags'       => ['production', 'critical'],
                'username'   => 'zepeed',
                'password'   => 's3cret-password',
                'timeout'    => 15,
                'verify_ssl' => true,
                'is_active'  => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Production notifications')
            ->assertJsonPath('data.tags', ['production', 'critical'])
            ->assertJsonPath('data.has_credentials', true)
            ->assertJsonPath('data.timeout', 15)
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('apprises', [
            'name'     => 'Production notifications',
            'url'      => 'https://apprise.example.com/notify/production',
            'username' => 'zepeed',
        ]);

        // Password is stored encrypted and decrypts back to the original.
        $this->assertSame(
            's3cret-password',
            Apprise::query()->firstOrFail()->password,
        );
    }

    /**
     * Test that creating an Apprise instance without Basic Auth works.
     */
    public function testAppriseCanBeCreatedWithoutAuthentication(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/apprise', [
                'name' => 'Open gateway',
                'url'  => 'https://apprise.example.com/notify',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.has_credentials', false)
            ->assertJsonPath('data.username', null);

        $this->assertDatabaseHas('apprises', [
            'name'      => 'Open gateway',
            'username'  => null,
            'password'  => null,
        ]);
    }

    /**
     * Test that creating an Apprise instance requires name and url.
     */
    public function testCreateRequiresNameAndUrl(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/apprise', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'url']);
    }

    /**
     * Test that creating an Apprise instance validates the URL format.
     */
    public function testCreateValidatesUrlFormat(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/apprise', [
                'name' => 'Broken',
                'url'  => 'not-a-url',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    /**
     * Test that tags must be strings.
     */
    public function testCreateValidatesTagsAreStrings(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/apprise', [
                'name' => 'Broken tags',
                'url'  => 'https://apprise.example.com/notify',
                'tags' => ['ok', 123],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tags.1']);
    }

    /**
     * Test that an authenticated user can view a single Apprise instance.
     */
    public function testAuthenticatedUserCanShowApprise(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->withTags(['dev'])->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/apprise/{$apprise->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $apprise->id)
            ->assertJsonPath('data.name', $apprise->name)
            ->assertJsonPath('data.tags', ['dev']);
    }

    /**
     * Test that an authenticated user can update an Apprise instance
     * including its tags.
     */
    public function testAuthenticatedUserCanUpdateApprise(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->withTags(['old'])->create(['name' => 'Old name']);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->patchJson("/api/v1/apprise/{$apprise->id}", [
                'name' => 'New name',
                'tags' => ['new', 'tags'],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New name')
            ->assertJsonPath('data.tags', ['new', 'tags']);

        $this->assertDatabaseHas('apprises', ['id' => $apprise->id, 'name' => 'New name']);
    }

    /**
     * Test that a blank password on update keeps the existing credential.
     */
    public function testUpdateBlankPasswordKeepsExistingCredential(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->withBasicAuth()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->patchJson("/api/v1/apprise/{$apprise->id}", [
                'name' => 'Renamed',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.has_credentials', true);

        $this->assertSame($apprise->password, $apprise->fresh()->password);
    }

    /**
     * Test that a non-empty password on update replaces the existing one.
     */
    public function testUpdateReplacesPasswordWhenProvided(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->withBasicAuth()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->patchJson("/api/v1/apprise/{$apprise->id}", [
                'password' => 'brand-new-password',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.has_credentials', true);

        $this->assertSame('brand-new-password', $apprise->fresh()->password);
    }

    /**
     * Test that an authenticated user can delete an Apprise instance.
     */
    public function testAuthenticatedUserCanDeleteApprise(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->deleteJson("/api/v1/apprise/{$apprise->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('apprises', ['id' => $apprise->id]);
    }

    /**
     * Test that a missing Apprise instance returns 404.
     */
    public function testMissingAppriseReturns404(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/apprise/nonexistent-uuid');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    /**
     * Test that an unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/apprise');

        $response->assertUnauthorized();
    }

    /**
     * Test that the test endpoint sends a notification and reports success.
     */
    public function testSendTestNotificationSucceeds(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->withTags(['critical'])->create();

        Http::fake();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/apprise/{$apprise->id}/test");

        $response->assertOk()
            ->assertJsonPath('success', true);

        Http::assertSent(fn (Request $request) => $request->url() === $apprise->url
            && $request['title'] === 'Zepeed test notification'
            && $request['tag'] === 'critical');

        $this->assertNotNull($apprise->fresh()->last_fired_at);
    }

    /**
     * Test that the test endpoint reports a failed HTTP response without
     * leaking credentials.
     */
    public function testSendTestNotificationReportsHttpFailure(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->withBasicAuth()->create();
        $password = $apprise->password;

        Http::fake([
            $apprise->url => Http::response(null, 500),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/apprise/{$apprise->id}/test");

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 422);

        $message = $response['message'];

        $this->assertStringContainsString('HTTP status 500', $message);
        $this->assertStringNotContainsString($password, $message);
        $this->assertNull($apprise->fresh()->last_fired_at);
    }

    /**
     * Test that the test endpoint reports a connection failure.
     */
    public function testSendTestNotificationReportsConnectionFailure(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $apprise = Apprise::factory()->create();

        Http::fake([
            $apprise->url => fn () => throw new ConnectionException('Connection refused'),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson("/api/v1/apprise/{$apprise->id}/test");

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertStringContainsString(
            'Connection refused',
            $response['message'],
        );
    }
}
