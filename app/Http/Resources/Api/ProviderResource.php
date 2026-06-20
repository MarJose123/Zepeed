<?php

namespace App\Http\Resources\Api;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Provider
 */
class ProviderResource extends JsonResource
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
            'id'               => $this->id,
            'slug'             => $this->slug->value,
            'name'             => $this->slug->label(),
            'enabled'          => $this->is_enabled,
            'is_healthy'       => $this->is_healthy,
            'last_run_at'      => $this->last_run_at?->toIso8601String(),
            'last_run_status'  => $this->last_run_status,
            'alert_on_failure' => $this->alert_on_failure,
        ];
    }
}
