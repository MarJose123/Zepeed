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

#[Description('Delete a ping alert rule by id. Requires the ping-alerts:delete token ability.')]
class DeletePingAlertRule extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::PingAlertsDelete);

        $validated = $request->validate([
            'id' => ['required', 'string'],
        ]);

        $pingAlertRule = PingAlertRule::query()->find($validated['id']);

        if ($pingAlertRule === null) {
            return Response::error('Ping alert rule not found.');
        }

        $pingAlertRule->delete();

        return Response::structured([
            'success' => true,
            'message' => 'Ping alert rule deleted successfully.',
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
