<?php

namespace App\Mcp\Tools;

use App\Enums\ExportModule;
use App\Enums\ExportStatus;
use App\Enums\TokenAbility;
use App\Jobs\GeneratePingResultExportJob;
use App\Jobs\GenerateSpeedResultExportJob;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\ExportRequest;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Queue an asynchronous export of speedtest or ping results (csv, xlsx, or json). Returns 202-style acceptance; the export becomes downloadable via get-export once its status is completed. Requires the exports:create token ability.')]
class CreateExport extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::ExportsCreate);

        $validated = $request->validate([
            'module'    => ['required', Rule::enum(ExportModule::class)],
            'format'    => ['required', 'in:csv,xlsx,json'],
            'provider'  => [
                'nullable',
                'string',
                'in:' . implode(',', Provider::query()->pluck('slug')->toArray()),
                'prohibited_if:module,ping_result',
            ],
            'target' => [
                'nullable',
                'uuid',
                'exists:ping_targets,id',
                'prohibited_unless:module,ping_result',
            ],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to'   => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $module = ExportModule::from($validated['module']);

        /** @var User $user */
        $user = $request->user();

        $filters = $module === ExportModule::PingResult
            ? [
                'target'    => $validated['target'] ?? null,
                'date_from' => $validated['date_from'],
                'date_to'   => $validated['date_to'],
            ]
            : [
                'provider'  => $validated['provider'] ?? null,
                'date_from' => $validated['date_from'],
                'date_to'   => $validated['date_to'],
            ];

        $export = ExportRequest::query()->create([
            'user_id' => $user->id,
            'module'  => $module,
            'format'  => $validated['format'],
            'status'  => ExportStatus::Pending,
            'filters' => $filters,
        ]);

        match ($module) {
            ExportModule::PingResult => dispatch(new GeneratePingResultExportJob($export)),
            default                  => dispatch(new GenerateSpeedResultExportJob($export)),
        };

        return Response::structured([
            'success' => true,
            'message' => 'Export queued successfully.',
            'export'  => $export->refresh(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'module'    => $schema->string()->description('speed_download, speed_upload, speed_latency, or ping_result.')->enum(['speed_download', 'speed_upload', 'speed_latency', 'ping_result'])->required(),
            'format'    => $schema->string()->description('csv, xlsx, or json.')->enum(['csv', 'xlsx', 'json'])->required(),
            'provider'  => $schema->string()->description('Provider slug filter (speedtest modules only).'),
            'target'    => $schema->string()->description('Ping target id (ping_result module only).'),
            'date_from' => $schema->string()->description('Start date, Y-m-d.')->required(),
            'date_to'   => $schema->string()->description('End date, Y-m-d. Must be after or equal to date_from.')->required(),
        ];
    }
}
