<?php

namespace App\Http\Resources;

use App\Models\MaintenanceWindow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class MaintenanceWindowResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        $window = $this->window();

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

    private function window(): MaintenanceWindow
    {
        /** @var MaintenanceWindow */
        return $this->resource;
    }
}
