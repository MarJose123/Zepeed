<?php

namespace Tests\Feature;

use App\Enums\EmailTemplateType;
use App\Enums\MailDriver;
use App\Enums\WorkflowRuleEvent;
use App\Models\EmailTemplate;
use App\Models\MailProvider;
use App\Models\SpeedResult;
use App\Models\User;
use App\Models\WorkflowRule;
use App\Models\WorkflowRuleAction;
use App\Services\MailProviderService;
use App\Services\WorkflowRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers mailer resolution for email actions. The reported bug: firing an
 * email action threw "Mailer [provider-id] is not defined." whenever the
 * provider was inactive or a long-running worker booted before the provider
 * existed — the runtime mailer config had no entry for it.
 */
class MailProviderMailerResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the dynamic failover mailer between tests (provider-specific
        // mailer names are random UUIDs so they never collide across tests).
        config([
            'mail.mailers.zepeed_failover' => null,
            'mail.default'                 => env('MAIL_MAILER', 'log'),
        ]);
    }

    private function makeProvider(bool $active = true): MailProvider
    {
        return MailProvider::query()->create([
            'driver'       => MailDriver::Smtp,
            'label'        => 'Test SMTP',
            'priority'     => 1,
            'is_active'    => $active,
            'config'       => [
                'host'       => 'smtp.example.com',
                'port'       => 587,
                'encryption' => 'tls',
                'username'   => 'user',
                'password'   => 'secret',
            ],
            'from_address' => 'sender@example.com',
            'from_name'    => 'Zepeed',
        ]);
    }

    public function testMailerForReturnsRegisteredProviderMailer(): void
    {
        $provider = $this->makeProvider();
        resolve(MailProviderService::class)->buildFailoverMailer();

        $mailer = resolve(MailProviderService::class)->mailerFor($provider->id);

        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertNotNull(config("mail.mailers.{$provider->id}"));
    }

    public function testMailerForResyncsProvidersCreatedAfterBoot(): void
    {
        // Simulates a long-running worker (queue:work / Octane) that booted
        // before the provider was created: no runtime mailer config exists yet.
        $provider = $this->makeProvider();

        $mailer = resolve(MailProviderService::class)->mailerFor($provider->id);

        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertNotNull(config("mail.mailers.{$provider->id}"));
    }

    public function testMailerForFallsBackToFailoverForInactiveProvider(): void
    {
        $this->makeProvider(active: true);
        $inactive = $this->makeProvider(active: false);
        resolve(MailProviderService::class)->buildFailoverMailer();

        $mailer = resolve(MailProviderService::class)->mailerFor($inactive->id);

        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertNull(config("mail.mailers.{$inactive->id}"));
        $this->assertNotNull(config('mail.mailers.zepeed_failover'));
    }

    public function testMailerForReturnsNullWhenNoMailerAvailable(): void
    {
        $inactive = $this->makeProvider(active: false);

        $mailer = resolve(MailProviderService::class)->mailerFor($inactive->id);

        $this->assertNull($mailer);
        $this->assertNull(config('mail.mailers.zepeed_failover'));
    }

    public function testMailerForReturnsNullWithoutProviderIdAndNoMailers(): void
    {
        $this->assertNull(resolve(MailProviderService::class)->mailerFor(null));
    }

    public function testMailerForDoesNotUseStaleMailerAfterProviderDeactivated(): void
    {
        // Two providers: A is active at "boot", B stays active as failover.
        $stale = $this->makeProvider(active: true);
        $active = $this->makeProvider(active: true);
        resolve(MailProviderService::class)->buildFailoverMailer();

        $this->assertNotNull(config("mail.mailers.{$stale->id}"));

        // A is deactivated later (e.g. via the UI) — the stale mailer entry
        // must be dropped and sends must not keep using its credentials.
        $stale->update(['is_active' => false]);

        $mailer = resolve(MailProviderService::class)->mailerFor($stale->id);

        $this->assertNull(config("mail.mailers.{$stale->id}"));
        $this->assertNotNull(config("mail.mailers.{$active->id}"));
        $this->assertInstanceOf(Mailer::class, $mailer); // failover via B
    }

    public function testDefaultMailerIsRestoredWhenNoProvidersRemain(): void
    {
        $originalDefault = config('mail.default');

        $provider = $this->makeProvider(active: true);
        resolve(MailProviderService::class)->buildFailoverMailer();
        $this->assertSame('zepeed_failover', config('mail.default'));

        // Deleting the last provider must restore the original default so
        // default-mailer sends (notification mail channels) don't throw
        // "Mailer [zepeed_failover] is not defined."
        $provider->delete();
        resolve(MailProviderService::class)->buildFailoverMailer();

        $this->assertSame($originalDefault, config('mail.default'));
        $this->assertNull(config('mail.mailers.zepeed_failover'));
    }

    /**
     * Creating a provider through the web UI re-registers the runtime mailers
     * in the same request, so email actions can use it without waiting for the
     * next process boot.
     */
    public function testCreatingProviderViaUiRegistersRuntimeMailer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/speedtest/integration/smtp', [
                'driver'        => 'smtp',
                'label'         => 'UI Created',
                'from_address'  => 'sender@example.com',
                'from_name'     => 'Zepeed',
                'is_active'     => true,
                'config'        => [
                    'host'       => 'smtp.example.com',
                    'port'       => 587,
                    'encryption' => 'tls',
                    'username'   => 'user',
                    'password'   => 'secret',
                ],
            ]);

        $response->assertRedirect();

        $provider = MailProvider::query()->where('label', 'UI Created')->firstOrFail();

        // The provider's mailer and the failover chain exist right away.
        $this->assertNotNull(config("mail.mailers.{$provider->id}"));
        $this->assertNotNull(config('mail.mailers.zepeed_failover'));
    }

    /**
     * Regression test for the reported error: an email action pointing at a
     * provider whose mailer is not registered must not throw
     * "Mailer [...] is not defined." — it is skipped gracefully and the rule
     * still fires.
     */
    public function testEmailActionWithoutMailerIsSkippedGracefully(): void
    {
        // Only an inactive provider exists, so no mailer is available at all.
        $provider = $this->makeProvider(active: false);

        $template = EmailTemplate::query()->create([
            'name'          => 'Speed threshold breach',
            'slug'          => 'test-' . Str::uuid(),
            'subject'       => 'Alert {{ $provider_name }}',
            'body'          => '<p>Hi</p>',
            'is_system'     => true,
            'template_type' => EmailTemplateType::Speedtest,
        ]);

        $rule = WorkflowRule::factory()->create([
            'event'     => WorkflowRuleEvent::RunCompletes,
            'is_active' => true,
        ]);

        WorkflowRuleAction::factory()->create([
            'workflow_rule_id'  => $rule->id,
            'type'              => 'email',
            'mail_provider_id'  => $provider->id,
            'email_template_id' => $template->id,
            'recipient_email'   => 'ops@example.com',
            'sort_order'        => 0,
        ]);

        $result = SpeedResult::factory()->success()->create();

        // Must not throw — previously this raised "Mailer [...] is not defined."
        resolve(WorkflowRuleService::class)->evaluate($result);

        // The rule still fired (cooldown timestamp updated).
        $this->assertNotNull($rule->fresh()->last_triggered_at);
    }
}
