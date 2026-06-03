<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

final class SpeedResultResource extends JsonResource
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
            'id'              => $this->id,
            'provider_slug'   => $this->provider_slug,
            'provider_name'   => $this->provider_name ?? $this->provider_slug,
            'status'          => $this->status,
            'download'        => $this->download_mbps !== null ? round((float) $this->download_mbps, 2) : null,
            'upload'          => $this->upload_mbps !== null ? round((float) $this->upload_mbps, 2) : null,
            'ping'            => $this->ping_ms !== null ? round((float) $this->ping_ms, 2) : null,
            'jitter'          => $this->jitter_ms !== null ? round((float) $this->jitter_ms, 2) : null,
            'server_name'     => $this->server_name,
            'server_location' => $this->server_location,
            'isp'             => $this->isp,
            'share_url'       => $this->share_url ?? null,
            'measured_at'     => $this->measured_at?->toIso8601String(),
        ];
    }
}
