<?php

namespace App\Http\Resources;

use App\Models\PingTarget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class PingTargetResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var PingTarget $target */
        $target = $this->resource;

        return [
            'id'                 => $target->id,
            'label'              => $target->label,
            'host'               => $target->host,
            'is_enabled'         => $target->is_enabled,
            'packets'            => $target->packets,
            'timeout_seconds'    => $target->timeout_seconds,
            'status'             => $target->status->value,
            'status_label'       => $target->status->label(),
            'last_avg_ms'        => $target->last_avg_ms,
            'last_loss_percent'  => $target->last_loss_percent,
            'last_tested_at'     => $target->last_tested_at?->toIso8601String(),
            'created_at'         => $target->created_at->toIso8601String(),
        ];
    }
}
