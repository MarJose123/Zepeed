<?php

namespace App\Mcp\Tools;

use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;
use Throwable;

#[Description('Send a synchronous test delivery to a webhook endpoint and record the delivery. Requires the webhooks:test token ability.')]
class TestWebhook extends Tool
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WebhookService $service,
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::WebhooksTest);

        $validated = $request->validate([
            'id' => ['required', 'string'],
        ]);

        $webhook = Webhook::query()->find($validated['id']);

        if ($webhook === null) {
            return Response::error('Webhook not found.');
        }

        try {
            $delivery = $this->service->sendTest($webhook);

            return Response::structured([
                'success'  => $delivery->success,
                'message'  => $delivery->success ? 'Test delivery succeeded.' : 'Test delivery failed.',
                'delivery' => $delivery,
            ]);
        } catch (Throwable $e) {
            return Response::structured([
                'success'  => false,
                'message'  => 'Test delivery failed: ' . $e->getMessage(),
                'delivery' => null,
            ]);
        }
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
