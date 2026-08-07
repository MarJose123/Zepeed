<?php

namespace App\Mcp\Tools;

use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\PingAlertRule;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Toggle the active state of a ping alert rule by id. Requires the ping-alerts:update token ability.')]
class TogglePingAlertRule extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::PingAlertsUpdate);

        $validated = $request->validate([
            'id' => ['required', 'string'],
        ]);

        $pingAlertRule = PingAlertRule::query()->find($validated['id']);

        if ($pingAlertRule === null) {
            return Response::error('Ping alert rule not found.');
        }

        $pingAlertRule->update(['is_active' => ! $pingAlertRule->is_active]);
        $pingAlertRule->load(['conditions', 'actions']);

        return Response::structured([
            'success'         => true,
            'message'         => $pingAlertRule->is_active ? 'Ping alert rule activated.' : 'Ping alert rule paused.',
            'ping_alert_rule' => $pingAlertRule->refresh(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Ping alert rule id (UUID).')->required(),
        ];
    }
}
