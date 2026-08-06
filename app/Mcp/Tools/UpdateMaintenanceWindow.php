<?php

namespace App\Mcp\Tools;

use App\Enums\MaintenanceWindowType;
use App\Enums\SpeedtestServer;
use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\MaintenanceWindow;
use Closure;
use Cron\CronExpression;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Update a maintenance window by id. Requires the maintenance:update token ability.')]
class UpdateMaintenanceWindow extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::MaintenanceUpdate);

        $validated = $request->validate($this->rules($request));

        $window = MaintenanceWindow::query()->find($request->get('id'));

        if ($window === null) {
            return Response::error('Maintenance window not found.');
        }

        $candidate = new MaintenanceWindow($validated);
        $candidate->id = $window->id; // Exclude the existing window from the overlap check.

        if ($candidate->overlapsWithExisting()) {
            return Response::error('This window overlaps with an existing active maintenance window for the same provider(s).');
        }

        $window->update($validated);

        return Response::structured([
            'success'            => true,
            'message'            => 'Maintenance window updated successfully.',
            'maintenance_window' => $window->refresh(),
        ]);
    }

    /**
     * Validation rules mirroring UpdateMaintenanceWindowRequest.
     *
     * @return array<string, mixed>
     */
    private function rules(Request $request): array
    {
        $type = MaintenanceWindowType::tryFrom((string) $request->get('type', ''));

        return [
            'label'       => ['required', 'string', 'max:100'],
            'type'        => ['required', 'string', 'in:' . implode(',', array_column(MaintenanceWindowType::cases(), 'value'))],
            'is_active'   => ['boolean'],
            'providers'   => ['required', 'array', 'min:1'],
            'providers.*' => [
                'string',
                'in:all,' . implode(',', array_column(SpeedtestServer::cases(), 'value')),
            ],
            'starts_at' => [
                $type?->requiresDateRange() ? 'required' : 'nullable',
                'date',
                'after:now',
                'before:ends_at',
            ],
            'ends_at' => [
                $type?->requiresDateRange() ? 'required' : 'nullable',
                'date',
                'after:starts_at',
                'after:now',
            ],
            'cron_expression' => [
                $type?->requiresCronExpression() ? 'required' : 'nullable',
                'string',
                static function (string $attribute, mixed $value, Closure $fail) {
                    if (! $value) {
                        return;
                    }

                    try {
                        new CronExpression($value);
                    } catch (InvalidArgumentException) {
                        $fail('The cron expression is invalid.');
                    }
                },
            ],
            'duration_minutes' => [
                $type?->requiresCronExpression() ? 'required' : 'nullable',
                'integer',
                'min:1',
                'max:1440',
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'id'                => $schema->string()->description('Maintenance window id (UUID).')->required(),
            'label'             => $schema->string()->description('Human-readable label.')->required(),
            'type'              => $schema->string()->description('one_time, recurring, or indefinite.')->enum(['one_time', 'recurring', 'indefinite'])->required(),
            'providers'         => $schema->array()->description('Provider slugs or "all".')->items($schema->string())->required(),
            'starts_at'         => $schema->string()->description('Required for one_time. Must be a future date/time (e.g. 2025-08-01 22:00:00).'),
            'ends_at'           => $schema->string()->description('Required for one_time. Must be after starts_at.'),
            'cron_expression'   => $schema->string()->description('Required for recurring. Valid cron expression.'),
            'duration_minutes'  => $schema->integer()->description('Required for recurring. 1-1440.')->min(1)->max(1440),
            'is_active'         => $schema->boolean()->default(true),
            'notes'             => $schema->string()->description('Max 500 characters.'),
        ];
    }
}
