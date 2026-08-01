<?php

namespace App\Http\Resources\Api;

use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin WebhookDelivery
 */
class WebhookDeliveryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var WebhookDelivery $delivery */
        $delivery = $this->resource;

        return [
            'id'            => $delivery->id,
            'webhook_id'    => $delivery->webhook_id,
            'event'         => $delivery->event,
            'status_code'   => $delivery->status_code,
            'status_text'   => $delivery->status_text,
            'duration_ms'   => $delivery->duration_ms,
            'attempt'       => $delivery->attempt,
            'max_attempts'  => $delivery->max_attempts,
            'success'       => $delivery->success,
            'response_body' => $delivery->response_body,
            'created_at'    => $delivery->created_at->toIso8601String(),
        ];
    }
}
