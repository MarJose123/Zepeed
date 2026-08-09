<?php

namespace App\Http\Resources\Api;

use App\Models\Apprise;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Apprise
 */
class AppriseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var Apprise $apprise */
        $apprise = $this->resource;

        return [
            'id'              => $apprise->id,
            'name'            => $apprise->name,
            'url'             => $apprise->url,
            'tags'            => $apprise->tags ?? [],
            'has_credentials' => $apprise->has_credentials,
            'username'        => $apprise->username,
            'timeout'         => $apprise->timeout,
            'verify_ssl'      => $apprise->verify_ssl,
            'is_active'       => $apprise->is_active,
            'last_fired_at'   => $apprise->last_fired_at?->toIso8601String(),
            'created_at'      => $apprise->created_at->toIso8601String(),
        ];
    }
}
