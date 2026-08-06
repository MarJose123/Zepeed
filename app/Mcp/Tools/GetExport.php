<?php

namespace App\Mcp\Tools;

use App\Enums\ExportFormat;
use App\Enums\ExportStatus;
use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\ExportRequest;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Show a single export request owned by the authenticated user. When include_content is true and the export completed as csv or json, the file content is included (truncated at 100KB). Requires the exports:view token ability.')]
class GetExport extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::ExportsView, TokenAbility::ExportsCreate);

        $validated = $request->validate([
            'id'              => ['required', 'string'],
            'include_content' => ['boolean'],
        ]);

        $export = ExportRequest::query()->find($validated['id']);

        /** @var User $user */
        $user = $request->user();

        if ($export === null || (int) $export->user_id !== (int) $user?->id) {
            return Response::error('Export not found.');
        }

        if ($export->expires_at !== null && $export->expires_at->isPast()) {
            return Response::error('Export has expired.');
        }

        $data = [
            'export'  => $export,
            'success' => true,
        ];

        if (($validated['include_content'] ?? false)
            && $export->status === ExportStatus::Completed
            && $export->file_path !== null
            && in_array($export->format, [ExportFormat::Csv, ExportFormat::Json], true)
        ) {
            $fullPath = storage_path("app/private/{$export->file_path}");

            if (file_exists($fullPath)) {
                $content = (string) file_get_contents($fullPath);
                $truncated = strlen($content) > 100_000;

                $data['content'] = $truncated ? substr($content, 0, 100_000) : $content;
                $data['truncated'] = $truncated;
            }
        }

        return Response::structured($data);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'id'              => $schema->string()->description('Export request id (UUID).')->required(),
            'include_content' => $schema->boolean()->description('Include file content for completed csv/json exports.')->default(false),
        ];
    }
}
