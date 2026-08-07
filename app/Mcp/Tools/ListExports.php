<?php

namespace App\Mcp\Tools;

use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\ExportRequest;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('List the authenticated user\'s export requests, newest first, with an optional status filter. Requires the exports:view token ability.')]
class ListExports extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::ExportsView, TokenAbility::ExportsCreate);

        $perPage = min(max((int) $request->get('per_page', 25), 1), 100);
        $page = max((int) $request->get('page', 1), 1);

        /** @var User $user */
        $user = $request->user();

        $query = ExportRequest::query()
            ->where('user_id', $user->id);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
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
            'per_page' => $schema->integer()->default(25)->min(1)->max(100),
            'page'     => $schema->integer()->default(1)->min(1),
            'status'   => $schema->string()->description('pending, processing, completed, or failed.'),
        ];
    }
}
