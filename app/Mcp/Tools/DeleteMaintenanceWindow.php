<?php

namespace App\Mcp\Tools;

use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\MaintenanceWindow;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Delete a maintenance window by id. Requires the maintenance:delete token ability.')]
class DeleteMaintenanceWindow extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::MaintenanceDelete);

        $validated = $request->validate([
            'id' => ['required', 'string'],
        ]);

        $window = MaintenanceWindow::query()->find($validated['id']);

        if ($window === null) {
            return Response::error('Maintenance window not found.');
        }

        $window->delete();

        return Response::structured([
            'success' => true,
            'message' => 'Maintenance window deleted successfully.',
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Maintenance window id (UUID).')->required(),
        ];
    }
}
