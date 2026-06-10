<?php

namespace App\Http\Resources;

use App\Models\ProviderSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin ProviderSchedule */
class ProviderScheduleResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'provider_slug'       => $this->provider_slug->value,
            'label'               => $this->label,
            'provider_name'       => $this->provider_slug->label(),
            'website_link'        => $this->provider_slug->websiteLink(),
            'cron_expression'     => $this->cron_expression,
            'is_enabled'          => $this->is_enabled,
            'provider_is_enabled' => $this->provider->is_enabled ?? false,
            'next_run_at'         => $this->nextRunAt()?->toIso8601String(),
            'last_scheduled_at'   => $this->last_scheduled_at?->toIso8601String(),
        ];
    }
}
