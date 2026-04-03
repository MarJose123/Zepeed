<?php

namespace App\Http\Resources;

use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class WebhookResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        $webhook = $this->webhook();

        return [
            'id'                 => $webhook->id,
            'name'               => $webhook->name,
            'url'                => $webhook->url,
            'url_preview'        => $this->buildUrlPreview($webhook->url),
            'method'             => $webhook->method,
            'has_secret'         => filled($webhook->secret),
            'headers'            => $webhook->headers ?? [],
            'timeout'            => $webhook->timeout,
            'retry_attempts'     => $webhook->retry_attempts,
            'verify_ssl'         => $webhook->verify_ssl,
            'is_active'          => $webhook->is_active,
            'last_fired_at'      => $webhook->last_fired_at?->toIso8601String(),
            'is_used_in_rules'   => $webhook->isUsedInRules(),
            'used_in_rule_names' => $webhook->usedInRuleNames(),
            'created_at'         => $webhook->created_at->toIso8601String(),
        ];
    }

    /**
     * Truncate URL for display — show host + first path segment.
     */
    private function buildUrlPreview(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? $url;
        $path = $parsed['path'] ?? '';
        $short = rtrim($host.substr($path, 0, 20), '/');

        return strlen($url) > strlen($short) ? $short.'…' : $short;
    }

    private function webhook(): Webhook
    {
        /** @var Webhook */
        return $this->resource;
    }
}
