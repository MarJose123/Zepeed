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

#[Description('Delete a webhook configuration by id. Requires the webhooks:delete token ability.')]
class DeleteWebhook extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::WebhooksDelete);

        $validated = $request->validate([
            'id' => ['required', 'string'],
        ]);

        $webhook = Webhook::query()->find($validated['id']);

        if ($webhook === null) {
            return Response::error('Webhook not found.');
        }

        $webhook->delete();

        return Response::structured([
            'success' => true,
            'message' => 'Webhook deleted successfully.',
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Webhook id (UUID).')->required(),
        ];
    }
}
