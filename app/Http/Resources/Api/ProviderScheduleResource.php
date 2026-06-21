<?php

namespace App\Http\Resources\Api;

use App\Models\ProviderSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin ProviderSchedule
 */
class ProviderScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'provider_slug'     => $this->provider_slug->value,
            'provider_name'     => $this->provider_slug->label(),
            'label'             => $this->label,
            'cron_expression'   => $this->cron_expression,
            'enabled'           => $this->is_enabled,
            'next_run_at'       => $this->nextRunAt()?->toIso8601String(),
            'last_scheduled_at' => $this->last_scheduled_at?->toIso8601String(),
        ];
    }
}
