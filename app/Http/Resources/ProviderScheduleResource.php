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
        $schedule = $this->schedule();

        return [
            'id'                => $schedule->id,
            'provider_slug'     => $schedule->provider_slug->value,
            'provider_name'     => $schedule->provider_slug->label(),
            'website_link'      => $schedule->provider_slug->websiteLink(),
            'cron_expression'   => $schedule->cron_expression,
            'is_enabled'        => $schedule->is_enabled,
            'next_run_at'       => $schedule->nextRunAt()?->toIso8601String(),
            'last_scheduled_at' => $schedule->last_scheduled_at?->toIso8601String(),
        ];
    }

    private function schedule(): ProviderSchedule
    {
        /** @var ProviderSchedule */
        return $this->resource;
    }
}
