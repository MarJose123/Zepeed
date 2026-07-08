<?php

namespace App\Mcp\Tools;

use App\Models\MaintenanceWindow;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('List maintenance windows with pagination, filtering, and sorting.')]
class ListMaintenanceWindows extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $perPage = min(max((int) $request->get('per_page', 25), 1), 100);
        $page = max((int) $request->get('page', 1), 1);

        // Inject filter values into the HTTP request so filterByQueryString can read them
        $queryParams = [];

        if ($request->has('starts_at_from')) {
            $queryParams['starts_at_from'] = $request->get('starts_at_from');
        }

        if ($request->has('starts_at_to')) {
            $queryParams['starts_at_to'] = $request->get('starts_at_to');
        }

        if ($request->has('is_active')) {
            $queryParams['is_active'] = $request->get('is_active');
        }

        if ($request->has('sort')) {
            $queryParams['sort'] = $request->get('sort');
        }

        request()->merge($queryParams);

        $results = MaintenanceWindow::query()
            ->filterByQueryString()
            ->sortByQueryString()
            ->paginate($perPage, ['*'], 'page', $page);

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
            'per_page'       => $schema->integer()->default(25)->min(1)->max(100),
            'page'           => $schema->integer()->default(1)->min(1),
            'starts_at_from' => $schema->string(),
            'starts_at_to'   => $schema->string(),
            'is_active'      => $schema->boolean(),
            'sort'           => $schema->object(),
        ];
    }
}
