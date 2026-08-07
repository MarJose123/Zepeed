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

#[Description('Delete a speedtest alert rule by id. Requires the alerts:delete token ability.')]
class DeleteAlertRule extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::AlertsDelete);

        $validated = $request->validate([
            'id' => ['required', 'string'],
        ]);

        $alertRule = AlertRule::query()->find($validated['id']);

        if ($alertRule === null) {
            return Response::error('Alert rule not found.');
        }

        $alertRule->delete();

        return Response::structured([
            'success' => true,
            'message' => 'Alert rule deleted successfully.',
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
