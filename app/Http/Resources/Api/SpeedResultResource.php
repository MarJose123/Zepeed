<?php

namespace App\Http\Resources\Api;

use App\Models\SpeedResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin SpeedResult
 */
class SpeedResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var SpeedResult $result */
        $result = $this->resource;

        return [
            'id'              => $result->id,
            'provider_slug'   => $result->provider_slug->value,
            'provider_name'   => $result->provider_slug->label(),
            'status'          => $result->status,
            'download'        => $result->download_mbps !== null ? round((float) $result->download_mbps, 2) : null,
            'upload'          => $result->upload_mbps !== null ? round((float) $result->upload_mbps, 2) : null,
            'ping'            => $result->ping_ms !== null ? round((float) $result->ping_ms, 2) : null,
            'jitter'          => $result->jitter_ms !== null ? round((float) $result->jitter_ms, 2) : null,
            'packet_loss'     => $result->packet_loss !== null ? round((float) $result->packet_loss, 2) : null,
            'server_name'     => $result->server_name,
            'server_location' => $result->server_location,
            'isp'             => $result->isp,
            'share_url'       => $result->share_url,
            'measured_at'     => $result->measured_at->toIso8601String(),
        ];
    }
}
