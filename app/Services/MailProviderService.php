<?php

namespace App\Services;

use App\Mail\TestConnectionMail;
use App\Models\MailProvider;
use Exception;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailProviderService
{
    /**
     * The default mailer from config/mail.php, remembered once per process
     * (captured at the first call — i.e. at boot, before the dynamic failover
     * chain overwrites it) so it can be restored when no providers remain.
     */
    private static ?string $originalDefaultMailer = null;

    /**
     * Build a dynamic failover mailer config from all active providers
     * ordered by priority and register it with Laravel at runtime.
     *
     * Runtime entries for providers that are no longer active (deactivated or
     * deleted after this process booted) are removed, so sends never go out
     * with stale credentials.
     */
    public function buildFailoverMailer(): void
    {
        self::$originalDefaultMailer ??= config('mail.default');

        $providers = MailProvider::query()
            ->active()
            ->ordered()
            ->get();

        $this->unregisterProviderMailers($providers->pluck('id')->all());

        if ($providers->isEmpty()) {
            config([
                'mail.mailers.zepeed_failover' => null,
                // Restore the default mailer so default-mailer sends (e.g.
                // notification mail channels) don't reference the now
                // unregistered failover chain.
                'mail.default' => self::$originalDefaultMailer,
            ]);

            return;
        }

        // Register each provider as a named mailer
        foreach ($providers as $provider) {
            config([
                "mail.mailers.{$provider->id}" => $provider->toMailerConfig(),
            ]);
        }

        // Build failover chain using provider UUIDs as mailer names
        config([
            'mail.mailers.zepeed_failover' => [
                'transport' => 'failover',
                'mailers'   => $providers->pluck('id')->all(),
            ],
            'mail.default' => 'zepeed_failover',
        ]);
    }

    /**
     * Resolve the mailer to use for an email action.
     *
     * Re-syncs the runtime mailer config from the database first so
     * long-running workers (queue:work / Octane) reflect providers created,
     * activated, or deactivated after boot. Prefers the given provider when
     * registered; otherwise falls back to the failover chain, or returns null
     * when no mailer is available so callers can skip gracefully.
     */
    public function mailerFor(?string $providerId): ?Mailer
    {
        $this->buildFailoverMailer();

        if ($this->isMailerRegistered($providerId)) {
            return $this->mailer($providerId);
        }

        if (config('mail.mailers.zepeed_failover') === null) {
            return null;
        }

        if ($providerId !== null && $providerId !== '') {
            Log::warning("MailProviderService: provider [{$providerId}] is not an active mailer; falling back to the failover chain.");
        }

        return $this->mailer('zepeed_failover');
    }

    /**
     * Resolve a registered named mailer as the concrete Illuminate\Mail\Mailer.
     * (Mail::mailer() is typed as the Mailer contract, which lacks html().)
     */
    private function mailer(string $name): Mailer
    {
        /** @var Mailer $mailer */
        $mailer = Mail::mailer($name);

        return $mailer;
    }

    /**
     * Whether a named mailer is registered in the runtime config.
     */
    private function isMailerRegistered(?string $mailerName): bool
    {
        return $mailerName !== null
            && $mailerName !== ''
            && config("mail.mailers.{$mailerName}") !== null;
    }

    /**
     * Remove runtime mailer entries for dynamic mailers that are no longer
     * valid: the failover chain, transient test_* mailers, and provider
     * (UUID) mailers whose provider was deactivated or deleted since the
     * process booted. Static mailers from config/mail.php are untouched.
     *
     * @param array<int, string> $activeIds
     */
    private function unregisterProviderMailers(array $activeIds): void
    {
        foreach (array_keys(config('mail.mailers', [])) as $name) {
            $isDynamic = $name === 'zepeed_failover'
                || str_starts_with($name, 'test_')
                || ($this->isUuid($name) && ! in_array($name, $activeIds, true));

            if ($isDynamic) {
                config(["mail.mailers.{$name}" => null]);
            }
        }
    }

    /**
     * Whether the given value looks like a provider UUID (dynamic mailer name).
     */
    private function isUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value,
        );
    }

    /**
     * Send a test email using a specific provider directly — bypassing failover.
     *
     * @throws Exception
     */
    public function sendTestEmail(MailProvider $provider, string $to): void
    {
        // Register this single provider as a temp mailer
        config([
            "mail.mailers.test_{$provider->id}" => $provider->toMailerConfig(),
        ]);

        Mail::mailer("test_{$provider->id}")
            ->to($to)
            ->send(new TestConnectionMail($provider));
    }

    /**
     * Reorder providers by the given ordered ID list.
     *
     * @param array<string> $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            MailProvider::query()
                ->where('id', $id)
                ->update(['priority' => $index + 1]);
        }
    }
}
