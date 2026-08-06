<?php

namespace App\Mcp\Tools;

use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Get application version and environment information.')]
class GetAppVersion extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::AppView);

        return Response::structured([
            'name'        => config('app.name'),
            'version'     => config('app.version'),
            'build_date'  => config('app.build_date'),
            'environment' => config('app.env'),
            'debug'       => config('app.debug'),
            'url'         => config('app.url'),
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
