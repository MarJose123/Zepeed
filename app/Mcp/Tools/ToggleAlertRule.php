<?php

namespace App\Mcp\Tools;

use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\AlertRule;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Toggle the active state of a speedtest alert rule by id. Requires the alerts:update token ability.')]
class ToggleAlertRule extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::AlertsUpdate);

        $validated = $request->validate([
            'id' => ['required', 'string'],
        ]);

        $alertRule = AlertRule::query()->find($validated['id']);

        if ($alertRule === null) {
            return Response::error('Alert rule not found.');
        }

        $alertRule->update(['is_active' => ! $alertRule->is_active]);
        $alertRule->load(['conditions', 'actions']);

        return Response::structured([
            'success'    => true,
            'message'    => $alertRule->is_active ? 'Alert rule activated.' : 'Alert rule paused.',
            'alert_rule' => $alertRule->refresh(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Alert rule id (UUID).')->required(),
        ];
    }
}
