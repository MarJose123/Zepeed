<?php

namespace App\Mcp\Tools;

use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\Webhook;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Create a new webhook configuration. The secret is stored encrypted and is never returned - only its presence is exposed via has_secret. Requires the webhooks:create token ability.')]
class CreateWebhook extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::WebhooksCreate);

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:100'],
            'url'             => ['required', 'url', 'max:500'],
            'method'          => ['required', 'in:POST,GET,PUT,PATCH'],
            'secret'          => ['nullable', 'string', 'max:500'],
            'headers'         => ['nullable', 'array'],
            'headers.*.key'   => ['required_with:headers', 'string', 'max:100'],
            'headers.*.value' => ['required_with:headers', 'string', 'max:500'],
            'timeout'         => ['integer', 'min:1', 'max:120'],
            'retry_attempts'  => ['integer', 'min:0', 'max:10'],
            'verify_ssl'      => ['boolean'],
            'is_active'       => ['boolean'],
        ]);

        $webhook = Webhook::query()->create($validated);

        return Response::structured([
            'success' => true,
            'message' => 'Webhook created successfully.',
            'webhook' => $webhook->refresh(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'name'           => $schema->string()->description('Human-readable name.')->required(),
            'url'            => $schema->string()->description('Endpoint URL.')->required(),
            'method'         => $schema->string()->description('POST, GET, PUT, or PATCH.')->enum(['POST', 'GET', 'PUT', 'PATCH'])->required(),
            'secret'         => $schema->string()->description('Optional signing secret (stored encrypted).'),
            'headers'        => $schema->array()->description('Optional custom headers: [{key, value}].')->items($schema->object([
                'key'   => $schema->string()->required(),
                'value' => $schema->string()->required(),
            ])),
            'timeout'        => $schema->integer()->description('Timeout in seconds, 1-120.')->default(30)->min(1)->max(120),
            'retry_attempts' => $schema->integer()->description('0-10.')->default(3)->min(0)->max(10),
            'verify_ssl'     => $schema->boolean()->default(true),
            'is_active'      => $schema->boolean()->default(true),
        ];
    }
}
