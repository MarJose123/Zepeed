<?php

namespace App\Mcp\Tools;

use App\Models\Provider;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('List configured speedtest providers with pagination, filtering, and sorting.')]
class ListProviders extends Tool
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

        if ($request->has('enabled')) {
            $queryParams['enabled'] = $request->get('enabled');
        }

        if ($request->has('sort')) {
            $queryParams['sort'] = $request->get('sort');
        }

        request()->merge($queryParams);

        $results = Provider::query()
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
            'per_page' => $schema->integer()->default(25)->min(1)->max(100),
            'page'     => $schema->integer()->default(1)->min(1),
            'enabled'  => $schema->boolean(),
            'sort'     => $schema->object(),
        ];
    }
}
