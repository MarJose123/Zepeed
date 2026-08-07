<?php

namespace App\Mcp\Tools;

use App\Enums\MaintenanceWindowType;
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

#[Description('Toggle the global indefinite pause on/off. When active, all providers are suppressed from running scheduled tests. Requires the maintenance:update token ability.')]
class ToggleGlobalPause extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::MaintenanceUpdate);

        $isCurrentlyActive = MaintenanceWindow::query()
            ->active()
            ->ofType(MaintenanceWindowType::Indefinite)
            ->whereJsonContains('providers', 'all')
            ->exists();

        MaintenanceWindow::toggleGlobalPause(! $isCurrentlyActive);

        return Response::structured([
            'success' => true,
            'message' => $isCurrentlyActive
                ? 'Global pause deactivated. All providers will resume their scheduled runs.'
                : 'Global pause activated. All speedtest runs are now suppressed.',
            'is_paused' => ! $isCurrentlyActive,
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
