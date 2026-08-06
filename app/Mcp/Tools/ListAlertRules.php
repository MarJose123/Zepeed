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

#[Description('List speedtest alert rules with their conditions and actions, with pagination and an optional is_active filter. Requires the alerts:view token ability.')]
class ListAlertRules extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::AlertsView, TokenAbility::AlertsCreate, TokenAbility::AlertsUpdate, TokenAbility::AlertsDelete);

        $perPage = min(max((int) $request->get('per_page', 25), 1), 100);
        $page = max((int) $request->get('page', 1), 1);

        $query = AlertRule::query()
            ->with(['conditions', 'actions']);

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->get('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $results = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return Response::structured([
            'data'       => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
                'last_page'    => $results->lastPage(),
            ],
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'per_page'  => $schema->integer()->default(25)->min(1)->max(100),
            'page'      => $schema->integer()->default(1)->min(1),
            'is_active' => $schema->boolean(),
        ];
    }
}
