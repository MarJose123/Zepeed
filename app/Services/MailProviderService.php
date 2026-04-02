<?php

namespace App\Services;

use App\Enums\QueueWorkerName;
use App\Mail\TestConnectionMail;
use App\Models\MailProvider;
use Exception;
use Illuminate\Support\Facades\Mail;

class MailProviderService
{
    /**
     * Build a dynamic failover mailer config from all active providers
     * ordered by priority and register it with Laravel at runtime.
     */
    public function buildFailoverMailer(): void
    {
        $providers = MailProvider::query()
            ->active()
            ->ordered()
            ->get();

        if ($providers->isEmpty()) {
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

        $message = new TestConnectionMail($provider)->onQueue(QueueWorkerName::Mail->value);

        Mail::mailer("test_{$provider->id}")
            ->to($to)
            ->queue($message);
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
