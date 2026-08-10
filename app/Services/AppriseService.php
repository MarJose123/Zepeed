<?php

namespace App\Services;

use App\Exceptions\AppriseException;
use App\Models\Apprise;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AppriseService
{
    /**
     * Send a notification to an Apprise API server.
     *
     * Each Apprise instance carries its own tags and (optional) Basic Auth
     * credentials; both are taken exclusively from the given instance, so
     * multiple instances never share or leak settings into one another.
     *
     * @param Apprise              $apprise
     * @param string               $title
     * @param string               $body
     * @param array<string, mixed> $options Supported keys:
     *                                      - type:   info|success|warning|failure (default: info)
     *                                      - format: text|markdown|html (default: text)
     *
     * @throws AppriseException When the request fails or returns a non-success status.
     *                          Credentials are never included in the message.
     */
    public function dispatch(Apprise $apprise, string $title, string $body, array $options = []): void
    {
        $payload = [
            'title'  => $title,
            'body'   => $body,
            'type'   => $options['type'] ?? 'info',
            'format' => $options['format'] ?? 'text',
        ];

        // Tags are sent as a comma-separated list and route the notification
        // to the matching services configured on the Apprise server.
        $tags = $apprise->tags ?? [];
        if ($tags !== []) {
            $payload['tag'] = implode(',', $tags);
        }

        $request = $this->buildRequest($apprise);

        try {
            $response = $request->post($apprise->url, $payload);

            if ($response->failed()) {
                throw new AppriseException(
                    "Apprise request to {$this->hostOf($apprise->url)} failed with HTTP status {$response->status()}.",
                    $response->status(),
                );
            }
        } catch (ConnectionException $e) {
            throw new AppriseException(
                "Could not reach Apprise at {$this->hostOf($apprise->url)}: {$e->getMessage()}",
            );
        }

        // Transient instances (creation-time connection checks) are never
        // persisted, so only track last_fired_at for stored instances.
        if ($apprise->exists) {
            $apprise->update(['last_fired_at' => now()]);
        }
    }

    /**
     * Send a test notification for a raw configuration payload without
     * persisting anything — used by the creation form's "Test connection"
     * button, which has no saved instance yet.
     *
     * @param array<string, mixed> $attributes Configuration attributes (name, url, tags, username, password, timeout, verify_ssl).
     *
     * @throws AppriseException
     */
    public function testConfiguration(array $attributes): void
    {
        $this->sendTest(new Apprise($attributes));
    }

    /**
     * Send a test notification to verify the instance is reachable and
     * correctly configured.
     *
     * @throws AppriseException
     */
    public function sendTest(Apprise $apprise): void
    {
        $this->dispatch(
            $apprise,
            'Zepeed test notification',
            "This is a test notification from Zepeed to verify the Apprise configuration \"{$apprise->name}\".",
            ['type' => 'info'],
        );
    }

    /**
     * Build the HTTP request for an Apprise instance.
     */
    private function buildRequest(Apprise $apprise): PendingRequest
    {
        $request = Http::timeout($apprise->timeout ?? 30)
            ->acceptJson()
            ->asJson();

        if (! ($apprise->verify_ssl ?? true)) {
            $request->withoutVerifying();
        }

        if (filled($apprise->username) && filled($apprise->password)) {
            $request->withBasicAuth($apprise->username, $apprise->password);
        }

        return $request;
    }

    /**
     * Resolve the host of an endpoint URL for error messages. Returns the
     * host only — userinfo (if any) and the full URL are never surfaced.
     */
    private function hostOf(string $url): string
    {
        return (string) (parse_url($url, PHP_URL_HOST) ?: $url);
    }
}
