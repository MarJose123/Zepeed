<?php

namespace App\Http\Resources\Api;

use App\Models\MaintenanceWindow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin MaintenanceWindow
 */
class MaintenanceWindowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var MaintenanceWindow $window */
        $window = $this->resource;

        return [
            'id'                  => $window->id,
            'label'               => $window->label,
            'type'                => $window->type->value,
            'type_label'          => $window->type->label(),
            'is_active'           => $window->is_active,
            'providers'           => $window->providers,
            'covers_all'          => $window->coversAllProviders(),
            'starts_at'           => $window->starts_at?->toIso8601String(),
            'ends_at'             => $window->ends_at?->toIso8601String(),
            'cron_expression'     => $window->cron_expression,
            'duration_minutes'    => $window->duration_minutes,
            'notes'               => $window->notes,
            'is_currently_active' => $window->isCurrentlyActive(),
            'created_at'          => $window->created_at->toIso8601String(),
        ];
    }
}
