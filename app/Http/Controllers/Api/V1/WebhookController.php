<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
use App\Http\Resources\Api\WebhookDeliveryResource;
use App\Http\Resources\Api\WebhookResource;
use App\Models\Webhook;
use App\Services\WebhookService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

#[Group(
    name: 'Webhooks',
    description: 'Manage outbound webhook configurations and inspect their delivery history. Webhooks are triggered by workflow rules (speedtest) or ping alert rules when their conditions match.',
    weight: 11,
)]
/**
 * Webhook Endpoints
 *
 * Manage outbound webhook configurations and inspect their delivery history.
 * Webhooks are triggered by workflow rules (speedtest) or ping alert rules
 * when their conditions match.
 */
class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $service,
    ) {}

    /**
     * List webhooks with pagination.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     */
    #[Endpoint(title: 'List webhooks', description: 'List webhook configurations with pagination.')]
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max((int) request()->query('per_page', 25), 1), 100);

        $webhooks = Webhook::query()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return WebhookResource::collection($webhooks)->additional([
            'success' => filled($webhooks),
            'code'    => 200,
        ]);
    }

    /**
     * Show a single webhook configuration.
     *
     * @param Webhook $webhook
     */
    #[Endpoint(title: 'Show webhook', description: 'Show a single webhook configuration.')]
    public function show(Webhook $webhook): JsonResource
    {
        return WebhookResource::make($webhook)->additional([
            'success' => true,
            'code'    => 200,
        ]);
    }

    /**
     * Create a new webhook.
     *
     * The `secret` is stored encrypted and is never returned by the API —
     * only its presence is exposed via `has_secret`.
     *
     * @param StoreWebhookRequest $request
     */
    #[Endpoint(title: 'Create webhook', description: 'Create a new webhook. The secret is stored encrypted and is never returned by the API - only its presence is exposed via has_secret.')]
    public function store(StoreWebhookRequest $request): JsonResponse
    {
        /** @var Webhook $webhook */
        $webhook = Webhook::query()->create($request->validated());

        return WebhookResource::make($webhook->refresh())
            ->additional([
                'success' => true,
                'code'    => 201,
                'message' => 'Webhook created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing webhook.
     *
     * @param UpdateWebhookRequest $request
     * @param Webhook              $webhook
     */
    #[Endpoint(title: 'Update webhook', description: 'Update an existing webhook configuration.')]
    public function update(UpdateWebhookRequest $request, Webhook $webhook): JsonResource
    {
        $webhook->update($request->validated());

        return WebhookResource::make($webhook->refresh())->additional([
            'success' => true,
            'code'    => 200,
            'message' => 'Webhook updated successfully.',
        ]);
    }

    /**
     * Delete a webhook.
     *
     * @param Webhook $webhook
     */
    #[Endpoint(title: 'Delete webhook', description: 'Delete a webhook configuration.')]
    public function destroy(Webhook $webhook): JsonResponse
    {
        $webhook->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Webhook deleted successfully.',
        ]);
    }

    /**
     * Send a test delivery to the webhook endpoint.
     *
     * Performs a synchronous HTTP request and records the delivery.
     * The response includes the recorded delivery regardless of success,
     * including connection failures (which are recorded and returned with 422).
     *
     * @param Webhook $webhook
     */
    #[Endpoint(title: 'Send test delivery', description: 'Send a synchronous test delivery to the webhook endpoint and record the delivery. Returns 200 on success and 422 when the delivery fails, including connection failures.')]
    public function test(Webhook $webhook): JsonResponse
    {
        try {
            $delivery = $this->service->sendTest($webhook);
        } catch (Exception $e) {
            $delivery = $webhook->deliveries()
                ->where('event', 'webhook.test')
                ->latest()
                ->first();

            // Only trust a freshly recorded failure. If the lookup returns a
            // stale (e.g. previously successful) row, fall back to a response
            // built from the exception itself.
            if ($delivery === null || $delivery->success) {
                return response()->json([
                    'success' => false,
                    'code'    => 422,
                    'message' => 'Test delivery failed: ' . $e->getMessage(),
                    'data'    => null,
                ], 422);
            }
        }

        $success = $delivery->success;

        return response()->json([
            'success' => $success,
            'code'    => $success ? 200 : 422,
            'message' => $success
                ? 'Test delivery succeeded.'
                : 'Test delivery failed.',
            'data'    => new WebhookDeliveryResource($delivery)->resolve(),
        ], $success ? 200 : 422);
    }

    /**
     * List delivery attempts for a webhook, newest first.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     *
     * @param Request $request
     * @param Webhook $webhook
     */
    #[Endpoint(title: 'List webhook deliveries', description: 'List delivery attempts for a webhook, newest first.')]
    public function deliveries(Request $request, Webhook $webhook): AnonymousResourceCollection
    {
        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);

        $deliveries = $webhook->deliveries()
            ->paginate($perPage)
            ->withQueryString();

        return WebhookDeliveryResource::collection($deliveries)->additional([
            'success' => filled($deliveries),
            'code'    => 200,
        ]);
    }
}
