<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class ProviderResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'slug'                => $this->slug->value,
            'name'                => $this->slug->label(),
            'website_link'        => $this->slug->websiteLink(),
            'requires_server_url' => $this->slug->requiresServerUrl(),
            'support_server_url'  => $this->slug->supportServerUrl(),
            'requires_chromium'   => $this->slug->requiresChromium(),
            'is_enabled'          => $this->is_enabled,
            'is_runnable'         => $this->is_runnable,   // ← new
            'is_healthy'          => $this->is_healthy,    // ← new
            'alert_on_failure'    => $this->alert_on_failure,
            'server_url'          => $this->server_url,
            'server_id'           => $this->server_id,
            'extra_flags'         => $this->extra_flags,
            'last_run_at'         => $this->last_run_at?->toIso8601String(),
            'last_run_status'     => $this->last_run_status,
            'status_badge'        => $this->status_badge,
        ];
    }
}
