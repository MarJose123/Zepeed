<?php

namespace App\Mcp\Tools;

use App\Models\SpeedResult;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('List speedtest results with pagination, filtering, sorting, and search.')]
class ListSpeedtestResults extends Tool
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

        if ($request->has('provider_slug')) {
            $queryParams['provider_slug'] = $request->get('provider_slug');
        }

        if ($request->has('measured_at_from')) {
            $queryParams['measured_at_from'] = $request->get('measured_at_from');
        }

        if ($request->has('measured_at_to')) {
            $queryParams['measured_at_to'] = $request->get('measured_at_to');
        }

        if ($request->has('sort')) {
            $queryParams['sort'] = $request->get('sort');
        }

        if ($request->has('search')) {
            $queryParams['search'] = $request->get('search');
        }

        request()->merge($queryParams);

        $results = SpeedResult::query()
            ->filterByQueryString()
            ->sortByQueryString()
            ->searchByQueryString()
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
            'per_page'         => $schema->integer()->default(25)->min(1)->max(100),
            'page'             => $schema->integer()->default(1)->min(1),
            'provider_slug'    => $schema->string(),
            'measured_at_from' => $schema->string(),
            'measured_at_to'   => $schema->string(),
            'sort'             => $schema->object(),
            'search'           => $schema->string(),
        ];
    }
}
