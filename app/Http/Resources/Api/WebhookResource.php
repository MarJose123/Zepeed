<?php

namespace App\Http\Resources\Api;

use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Webhook
 */
class WebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var Webhook $webhook */
        $webhook = $this->resource;

        return [
            'id'             => $webhook->id,
            'name'           => $webhook->name,
            'url'            => $webhook->url,
            'method'         => $webhook->method,
            'has_secret'     => filled($webhook->secret),
            'headers'        => $webhook->headers ?? [],
            'timeout'        => $webhook->timeout,
            'retry_attempts' => $webhook->retry_attempts,
            'verify_ssl'     => $webhook->verify_ssl,
            'is_active'      => $webhook->is_active,
            'last_fired_at'  => $webhook->last_fired_at?->toIso8601String(),
            'created_at'     => $webhook->created_at->toIso8601String(),
        ];
    }
}
