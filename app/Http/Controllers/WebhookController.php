<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
use App\Http\Resources\WebhookDeliveryResource;
use App\Http\Resources\WebhookResource;
use App\Models\Webhook;
use App\Services\InertiaNotification;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $service,
    ) {}

    public function index(): Response
    {
        $webhooks = Webhook::query()
            ->latest()
            ->get();

        // Load deliveries for the first webhook by default
        $firstWebhook = $webhooks->first();

        return Inertia::render('integration/Webhooks', [
            'webhooks'   => WebhookResource::collection($webhooks)->resolve(),
            'deliveries' => $firstWebhook
                ? WebhookDeliveryResource::collection(
                    $firstWebhook->deliveries()->limit(20)->get()
                )->resolve()
                : [],
            'selected_webhook_id' => $firstWebhook?->id,
        ]);
    }

    /**
     * Paginated deliveries for a specific webhook — used by "View all".
     */
    public function deliveries(Request $request, Webhook $webhook): Response
    {
        $deliveries = $webhook->deliveries()
            ->paginate(50);

        return Inertia::render('integration/WebhookDeliveries', [
            'webhook'    => new WebhookResource($webhook)->resolve(),
            'deliveries' => WebhookDeliveryResource::collection($deliveries),
        ]);
    }

    /**
     * JSON endpoint — swap delivery log when selecting a different card.
     */
    public function deliveriesJson(Webhook $webhook): JsonResponse
    {
        return response()->json(
            WebhookDeliveryResource::collection(
                $webhook->deliveries()->limit(20)->get()
            )->resolve()
        );
    }

    public function store(StoreWebhookRequest $request): RedirectResponse
    {
        Webhook::query()->create($request->validated());

        InertiaNotification::make()
            ->success()
            ->title('Webhook created')
            ->message("Webhook \"{$request->validated('name')}\" has been added.")
            ->send();

        return back();
    }

    public function update(
        UpdateWebhookRequest $request,
        Webhook $webhook,
    ): RedirectResponse {
        $webhook->update($request->validated());

        InertiaNotification::make()
            ->success()
            ->title('Webhook updated')
            ->message("\"{$webhook->name}\" has been updated.")
            ->send();

        return back();
    }

    public function destroy(Webhook $webhook): RedirectResponse
    {
        $name = $webhook->name;
        $webhook->delete();

        InertiaNotification::make()
            ->success()
            ->title('Webhook removed')
            ->message("\"{$name}\" has been removed.")
            ->send();

        return back();
    }

    public function test(Request $request, Webhook $webhook): RedirectResponse
    {
        try {
            $delivery = $this->service->sendTest($webhook);

            if ($delivery->success) {
                InertiaNotification::make()
                    ->success()
                    ->title('Test delivery succeeded')
                    ->message(
                        "\"{$webhook->name}\" responded with {$delivery->status_code} "
                        ."in {$delivery->duration_ms}ms."
                    )
                    ->send();
            } else {
                InertiaNotification::make()
                    ->warning()
                    ->title('Test delivery failed')
                    ->message(
                        "\"{$webhook->name}\" returned {$delivery->status_code}: "
                        ."{$delivery->status_text}."
                    )
                    ->send();
            }
        } catch (Throwable $e) {
            InertiaNotification::make()
                ->error()
                ->title('Connection failed')
                ->message($e->getMessage())
                ->send();
        }

        return back();
    }
}
