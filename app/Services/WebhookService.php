<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Spatie\WebhookServer\Exceptions\InvalidSigner;
use Spatie\WebhookServer\Signer\DefaultSigner;
use Spatie\WebhookServer\WebhookCall;
use Throwable;

class WebhookService
{
    /**
     * Dispatch a webhook call via Spatie webhook-server.
     *
     * @param Webhook              $webhook
     * @param string               $event
     * @param array<string, mixed> $payload
     *
     * @throws InvalidSigner
     */
    public function dispatch(Webhook $webhook, string $event, array $payload): void
    {
        $call = WebhookCall::create()
            ->url($webhook->url)
            ->payload(array_merge($payload, ['event' => $event]))
            ->useHttpVerb($webhook->method)
            ->timeoutInSeconds($webhook->timeout)
            ->maximumTries($webhook->retry_attempts ?: 0)
            ->verifySsl($webhook->verify_ssl);

        // Sign the payload if secret is set
        if (filled($webhook->secret)) {
            $call->signUsing(DefaultSigner::class)
                ->doNotSign()  // reset default
                ->withHeaders(['X-Webhook-Secret' => $webhook->secret]);

            $call->signUsing(DefaultSigner::class);
        } else {
            $call->doNotSign();
        }

        // Add custom headers
        if (! empty($webhook->headers)) {
            $headers = collect($webhook->headers)
                ->pluck('value', 'key')
                ->all();
            $call->withHeaders($headers);
        }

        $call->dispatch();

        $webhook->update(['last_fired_at' => now()]);
    }

    /**
     * Send a test ping to verify the endpoint is reachable.
     *
     * @throws Throwable
     */
    public function sendTest(Webhook $webhook): WebhookDelivery
    {
        $start = microtime(true);

        try {
            $response = Http::timeout($webhook->timeout)
                ->unless($webhook->verify_ssl, fn ($h) => $h->withoutVerifying())
                ->withHeaders($this->buildHeaders($webhook))
                ->{strtolower($webhook->method)}($webhook->url, [
                    'event'   => 'webhook.test',
                    'message' => 'This is a test delivery from Zepeed.',
                ]);

            $duration = (int) ((microtime(true) - $start) * 1000);

            return WebhookDelivery::query()->create([
                'webhook_id'    => $webhook->id,
                'event'         => 'webhook.test',
                'status_code'   => $response->status(),
                'status_text'   => $response->successful() ? 'OK' : $response->reason(),
                'duration_ms'   => $duration,
                'attempt'       => 1,
                'max_attempts'  => 1, // Always 1 for test
                'success'       => $response->successful(),
                'response_body' => substr((string) $response->body(), 0, 1000),
            ]);

        } catch (Throwable $e) {
            $duration = (int) ((microtime(true) - $start) * 1000);

            WebhookDelivery::query()->create([
                'webhook_id'    => $webhook->id,
                'event'         => 'webhook.test',
                'status_code'   => null,
                'status_text'   => 'Connection failed',
                'duration_ms'   => $duration,
                'attempt'       => 1,
                'max_attempts'  => 1, // Always 1 for test
                'success'       => false,
                'response_body' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(Webhook $webhook): array
    {
        if (empty($webhook->headers)) {
            return [];
        }

        return collect($webhook->headers)
            ->pluck('value', 'key')
            ->all();
    }
}
