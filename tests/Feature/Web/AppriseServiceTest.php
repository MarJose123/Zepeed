<?php

namespace Tests\Feature\Web;

use App\Exceptions\AppriseException;
use App\Models\Apprise;
use App\Services\AppriseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Unit-level tests for AppriseService. All external Apprise requests are
 * mocked with Http::fake() so no Apprise server is required.
 */
class AppriseServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppriseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(AppriseService::class);
    }

    /**
     * Test that a notification is sent to the instance URL with the expected
     * payload.
     */
    public function testDispatchSendsNotificationToInstanceUrl(): void
    {
        $apprise = Apprise::factory()->create([
            'url' => 'https://apprise.example.com/notify/production',
        ]);

        Http::fake();

        $this->service->dispatch(
            $apprise,
            'Test title',
            'Test body',
            ['type' => 'success'],
        );

        Http::assertSent(fn (Request $request) => $request->url() === 'https://apprise.example.com/notify/production'
            && $request['title'] === 'Test title'
            && $request['body'] === 'Test body'
            && $request['type'] === 'success'
            && $request['format'] === 'text');
    }

    /**
     * Test that multiple tags are transmitted as a comma-separated list.
     */
    public function testDispatchSendsAllConfiguredTagsCommaSeparated(): void
    {
        $apprise = Apprise::factory()->withTags(['production', 'critical'])->create();

        Http::fake();

        $this->service->dispatch($apprise, 'Title', 'Body');

        Http::assertSent(fn (Request $request) => $request['tag'] === 'production,critical');
    }

    /**
     * Test that the tag parameter is omitted when no tags are configured.
     */
    public function testDispatchOmitsTagWhenNoTagsConfigured(): void
    {
        $apprise = Apprise::factory()->create(['tags' => null]);

        Http::fake();

        $this->service->dispatch($apprise, 'Title', 'Body');

        Http::assertSent(fn (Request $request) => ! array_key_exists('tag', $request->data()));
    }

    /**
     * Test that Basic Auth credentials are transmitted when configured.
     */
    public function testDispatchSendsBasicAuthWhenCredentialsConfigured(): void
    {
        $apprise = Apprise::factory()->withBasicAuth()->create([
            'username' => 'zepeed-user',
            'password' => 'zepeed-pass',
        ]);

        Http::fake();

        $this->service->dispatch($apprise, 'Title', 'Body');

        Http::assertSent(fn (Request $request) => $request->hasHeader(
            'Authorization',
            'Basic ' . base64_encode('zepeed-user:zepeed-pass'),
        ));

        // The password must never be part of the payload itself.
        Http::assertSent(fn (Request $request) => ! array_key_exists('password', $request->data()));
    }

    /**
     * Test that no Authorization header is sent without credentials.
     */
    public function testDispatchOmitsBasicAuthWithoutCredentials(): void
    {
        $apprise = Apprise::factory()->create(['username' => null, 'password' => null]);

        Http::fake();

        $this->service->dispatch($apprise, 'Title', 'Body');

        Http::assertSent(fn (Request $request) => ! $request->hasHeader('Authorization'));
    }

    /**
     * Test that Basic Auth is not sent when only a username is configured.
     */
    public function testDispatchOmitsBasicAuthWhenPasswordMissing(): void
    {
        $apprise = Apprise::factory()->create([
            'username' => 'user-without-password',
            'password' => null,
        ]);

        Http::fake();

        $this->service->dispatch($apprise, 'Title', 'Body');

        Http::assertSent(fn (Request $request) => ! $request->hasHeader('Authorization'));
    }

    /**
     * Test that multiple instances keep their own settings, tags and
     * credentials — nothing bleeds from one configuration into another.
     */
    public function testMultipleInstancesStayIsolated(): void
    {
        $production = Apprise::factory()->withTags(['production', 'critical'])->create([
            'url'      => 'https://prod.apprise.test/notify',
            'username' => 'prod-user',
            'password' => 'prod-pass',
        ]);
        $development = Apprise::factory()->withTags(['development'])->create([
            'url'      => 'https://dev.apprise.test/notify',
            'username' => null,
            'password' => null,
        ]);

        Http::fake();

        $this->service->dispatch($production, 'Prod title', 'Prod body');
        $this->service->dispatch($development, 'Dev title', 'Dev body');

        Http::assertSentCount(2);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://prod.apprise.test/notify'
            && $request['title'] === 'Prod title'
            && $request['tag'] === 'production,critical'
            && $request->hasHeader(
                'Authorization',
                'Basic ' . base64_encode('prod-user:prod-pass'),
            ));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://dev.apprise.test/notify'
            && $request['title'] === 'Dev title'
            && $request['tag'] === 'development'
            && ! $request->hasHeader('Authorization'));
    }

    /**
     * Test that two instances pointing at the same URL but different tags send
     * their own tags without overwriting each other.
     */
    public function testSameUrlDifferentTagsAreNotMerged(): void
    {
        $first = Apprise::factory()->withTags(['alpha'])->create([
            'url' => 'https://shared.apprise.test/notify',
        ]);
        $second = Apprise::factory()->withTags(['beta'])->create([
            'url' => 'https://shared.apprise.test/notify',
        ]);

        Http::fake();

        $this->service->dispatch($first, 'A', 'A');
        $this->service->dispatch($second, 'B', 'B');

        Http::assertSent(fn (Request $request) => $request['tag'] === 'alpha' && $request['title'] === 'A');
        Http::assertSent(fn (Request $request) => $request['tag'] === 'beta' && $request['title'] === 'B');
    }

    /**
     * Test that a non-success HTTP response throws a sanitized AppriseException.
     */
    public function testDispatchThrowsOnHttpErrorWithSanitizedMessage(): void
    {
        $apprise = Apprise::factory()->withBasicAuth()->create([
            'url' => 'https://apprise.example.com/notify',
        ]);

        Http::fake([
            'https://apprise.example.com/*' => Http::response(null, 503),
        ]);

        try {
            $this->service->dispatch($apprise, 'Title', 'Body');
            $this->fail('Expected AppriseException was not thrown.');
        } catch (AppriseException $e) {
            $this->assertSame(503, $e->statusCode);
            $this->assertStringContainsString('apprise.example.com', $e->getMessage());
            $this->assertStringContainsString('HTTP status 503', $e->getMessage());
            // Credentials must never surface in the error message.
            $this->assertStringNotContainsString($apprise->password, $e->getMessage());
            $this->assertStringNotContainsString($apprise->username, $e->getMessage());
        }
    }

    /**
     * Test that a connection failure throws a sanitized AppriseException.
     */
    public function testDispatchThrowsOnConnectionFailure(): void
    {
        $apprise = Apprise::factory()->create([
            'url' => 'https://unreachable.apprise.test/notify',
        ]);

        Http::fake([
            'https://unreachable.apprise.test/*' => fn () => throw new ConnectionException('Connection refused'),
        ]);

        try {
            $this->service->dispatch($apprise, 'Title', 'Body');
            $this->fail('Expected AppriseException was not thrown.');
        } catch (AppriseException $e) {
            $this->assertNull($e->statusCode);
            $this->assertStringContainsString('unreachable.apprise.test', $e->getMessage());
            $this->assertStringContainsString('Connection refused', $e->getMessage());
        }
    }

    /**
     * Test that last_fired_at is updated on success only.
     */
    public function testLastFiredAtUpdatedOnSuccessOnly(): void
    {
        $success = Apprise::factory()->create(['url' => 'https://ok.apprise.test/notify']);
        $failing = Apprise::factory()->create(['url' => 'https://fail.apprise.test/notify']);

        Http::fake([
            'https://ok.apprise.test/*'   => Http::response(null, 200),
            'https://fail.apprise.test/*' => Http::response(null, 500),
        ]);

        $this->service->dispatch($success, 'Title', 'Body');

        try {
            $this->service->dispatch($failing, 'Title', 'Body');
            $this->fail('Expected AppriseException was not thrown.');
        } catch (AppriseException) {
            // expected
        }

        $this->assertNotNull($success->fresh()->last_fired_at);
        $this->assertNull($failing->fresh()->last_fired_at);
    }

    /**
     * Test that a failing instance does not affect dispatching to other
     * instances.
     */
    public function testFailureOfOneInstanceDoesNotAffectOthers(): void
    {
        $failing = Apprise::factory()->create(['url' => 'https://fail.apprise.test/notify']);
        $working = Apprise::factory()->create(['url' => 'https://ok.apprise.test/notify']);

        Http::fake([
            'https://fail.apprise.test/*' => Http::response(null, 500),
            'https://ok.apprise.test/*'   => Http::response(null, 200),
        ]);

        try {
            $this->service->dispatch($failing, 'Title', 'Body');
            $this->fail('Expected AppriseException was not thrown.');
        } catch (AppriseException) {
            // expected
        }

        $this->service->dispatch($working, 'Title', 'Body');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://ok.apprise.test/notify');
    }

    /**
     * Test that sendTest sends a recognizable test notification.
     */
    public function testSendTestSendsTestNotification(): void
    {
        $apprise = Apprise::factory()->withTags(['test'])->create();

        Http::fake();

        $this->service->sendTest($apprise);

        Http::assertSent(fn (Request $request) => $request['title'] === 'Zepeed test notification'
            && $request['type'] === 'info'
            && $request['tag'] === 'test');

        $this->assertNotNull($apprise->fresh()->last_fired_at);
    }

    /**
     * Test that a raw configuration payload can be tested (creation-time
     * connection check) without persisting anything.
     */
    public function testTestConfigurationSendsNotificationWithoutPersisting(): void
    {
        Http::fake();

        $this->service->testConfiguration([
            'name'       => 'Draft config',
            'url'        => 'https://draft.apprise.test/notify',
            'tags'       => ['draft'],
            'username'   => null,
            'password'   => null,
            'timeout'    => 15,
            'verify_ssl' => true,
        ]);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://draft.apprise.test/notify'
            && $request['title'] === 'Zepeed test notification'
            && $request['tag'] === 'draft');

        // Nothing was written to the database.
        $this->assertDatabaseCount('apprises', 0);
    }

    /**
     * Test that a failed configuration test throws without persisting.
     */
    public function testTestConfigurationThrowsOnFailureWithoutPersisting(): void
    {
        Http::fake([
            'https://draft.apprise.test/*' => Http::response(null, 500),
        ]);

        try {
            $this->service->testConfiguration([
                'name' => 'Draft config',
                'url'  => 'https://draft.apprise.test/notify',
            ]);
            $this->fail('Expected AppriseException was not thrown.');
        } catch (AppriseException $e) {
            $this->assertSame(500, $e->statusCode);
        }

        $this->assertDatabaseCount('apprises', 0);
    }

    /**
     * Test that the type option can be overridden.
     */
    public function testDispatchPassesThroughTypeAndFormatOptions(): void
    {
        $apprise = Apprise::factory()->create();

        Http::fake();

        $this->service->dispatch($apprise, 'Title', 'Body', [
            'type'   => 'failure',
            'format' => 'markdown',
        ]);

        Http::assertSent(fn (Request $request) => $request['type'] === 'failure' && $request['format'] === 'markdown');
    }
}
