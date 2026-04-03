<?php

namespace App\Http\Resources;

use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class WebhookDeliveryResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        $delivery = $this->delivery();

        return [
            'id'           => $delivery->id,
            'event'        => $delivery->event,
            'status_code'  => $delivery->status_code,
            'status_text'  => $delivery->status_text,
            'duration_ms'  => $delivery->duration_ms,
            'attempt'      => $delivery->attempt,
            'max_attempts' => $delivery->max_attempts,
            'success'      => $delivery->success,
            'created_at'   => $delivery->created_at->toIso8601String(),
        ];
    }

    private function delivery(): WebhookDelivery
    {
        /** @var WebhookDelivery */
        return $this->resource;
    }
}
