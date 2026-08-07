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

#[Description('Update an existing webhook configuration by id. Requires the webhooks:update token ability.')]
class UpdateWebhook extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::WebhooksUpdate);

        $validated = $request->validate([
            'id'              => ['required', 'string'],
            'name'            => ['sometimes', 'string', 'max:100'],
            'url'             => ['sometimes', 'url', 'max:500'],
            'method'          => ['sometimes', 'in:POST,GET,PUT,PATCH'],
            'secret'          => ['nullable', 'string', 'max:500'],
            'headers'         => ['nullable', 'array'],
            'headers.*.key'   => ['required_with:headers', 'string', 'max:100'],
            'headers.*.value' => ['required_with:headers', 'string', 'max:500'],
            'timeout'         => ['sometimes', 'integer', 'min:1', 'max:120'],
            'retry_attempts'  => ['sometimes', 'integer', 'min:0', 'max:10'],
            'verify_ssl'      => ['boolean'],
            'is_active'       => ['boolean'],
        ]);

        $webhook = Webhook::query()->find($validated['id']);

        if ($webhook === null) {
            return Response::error('Webhook not found.');
        }

        unset($validated['id']);

        $webhook->update($validated);

        return Response::structured([
            'success' => true,
            'message' => 'Webhook updated successfully.',
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
            'id'             => $schema->string()->description('Webhook id (UUID).')->required(),
            'name'           => $schema->string()->description('Human-readable name.'),
            'url'            => $schema->string()->description('Endpoint URL.'),
            'method'         => $schema->string()->description('POST, GET, PUT, or PATCH.')->enum(['POST', 'GET', 'PUT', 'PATCH']),
            'secret'         => $schema->string()->description('Optional signing secret (stored encrypted).'),
            'headers'        => $schema->array()->description('Optional custom headers: [{key, value}].')->items($schema->object([
                'key'   => $schema->string()->required(),
                'value' => $schema->string()->required(),
            ])),
            'timeout'        => $schema->integer()->description('Timeout in seconds, 1-120.')->min(1)->max(120),
            'retry_attempts' => $schema->integer()->description('0-10.')->min(0)->max(10),
            'verify_ssl'     => $schema->boolean(),
            'is_active'      => $schema->boolean(),
        ];
    }
}
