<?php

namespace App\Http\Resources\Api;

use App\Models\PingResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin PingResult
 */
class PingResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var PingResult $result */
        $result = $this->resource;

        return [
            'id'                  => $result->id,
            'ping_target_id'      => $result->ping_target_id,
            'target_label'        => $result->relationLoaded('target') ? $result->target->label : null,
            'target_host'         => $result->relationLoaded('target') ? $result->target->host : null,
            'status'              => $result->status->value,
            'status_label'        => $result->status->label(),
            'packets_sent'        => $result->packets_sent,
            'packets_received'    => $result->packets_received,
            'packet_loss_percent' => $result->packet_loss_percent,
            'min_ms'              => $result->min_ms,
            'avg_ms'              => $result->avg_ms,
            'max_ms'              => $result->max_ms,
            'stddev_ms'           => $result->stddev_ms,
            'measured_at'         => $result->measured_at->toIso8601String(),
        ];
    }
}
