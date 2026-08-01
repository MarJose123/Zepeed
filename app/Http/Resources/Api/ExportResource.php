<?php

namespace App\Http\Resources\Api;

use App\Models\ExportRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin ExportRequest
 */
class ExportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var ExportRequest $export */
        $export = $this->resource;

        return [
            'id'              => $export->id,
            'module'          => $export->module->value,
            'module_label'    => $export->module->label(),
            'format'          => $export->format->value,
            'status'          => $export->status->value,
            'filters'         => $export->filters,
            'row_count'       => $export->row_count,
            'failure_message' => $export->failure_message,
            'expires_at'      => $export->expires_at?->toIso8601String(),
            'download_url'    => $export->status->value === 'completed'
                ? route('api.exports.download', $export, false)
                : null,
            'created_at'      => $export->created_at->toIso8601String(),
        ];
    }
}
