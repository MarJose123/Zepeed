<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QueueWorkerName;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProviderRequest;
use App\Http\Resources\Api\ProviderResource;
use App\Jobs\RunSpeedtestJob;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

#[Group(
    name: 'Providers',
    description: 'Provides access to speedtest provider information and their current status. Providers are the various speedtest services (Ookla, LibreSpeed, Cloudflare, etc.) configured in the system.',
    weight: 7,
)]
/**
 * Provider Management Endpoints
 *
 * Provides access to speedtest provider information and their current status.
 * Providers are the various speedtest services (Ookla, LibreSpeed, Cloudflare, etc.)
 * configured in the system.
 */
class ProviderController extends Controller
{
    /**
     * List all configured providers with pagination, filtering, and sorting.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     * @queryParam enabled boolean Filter by enabled status (0 or 1).
     * @queryParam sort array Sort by field: ?sort[name]=asc or ?sort[created_at]=desc.
     */
    #[Endpoint(title: 'List providers', description: 'List all configured providers with pagination, filtering, and sorting.')]
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max((int) request()->query('per_page', 25), 1), 100);

        $providers = Provider::query()
            ->with(['latestResult', 'latestSuccessfulResult'])
            ->filterByQueryString()
            ->sortByQueryString()
            ->paginate($perPage)
            ->withQueryString();

        return ProviderResource::collection($providers)->additional([
            'success' => filled($providers),
            'code'    => 200,
        ]);
    }

    /**
     * Update a provider's configuration.
     *
     * When a previously enabled provider is disabled, all its active
     * schedules are also disabled so they do not linger as orphaned
     * scheduled runs.
     *
     * @bodyParam is_enabled boolean required Whether the provider is enabled.
     * @bodyParam alert_on_failure boolean required Whether to alert when a run fails.
     * @bodyParam server_url string nullable Endpoint URL (required to enable LibreSpeed).
     * @bodyParam server_id string nullable Numeric Ookla server ID (optional).
     *
     * @param UpdateProviderRequest $request
     * @param Provider              $provider
     */
    #[Endpoint(title: 'Update provider', description: "Update a provider's configuration. When a previously enabled provider is disabled, all its active schedules are also disabled.")]
    public function update(UpdateProviderRequest $request, Provider $provider): JsonResource
    {
        $wasEnabled = $provider->is_enabled;
        $willDisable = $wasEnabled && ! $request->boolean('is_enabled');

        $provider->update($request->validated());

        if ($willDisable) {
            ProviderSchedule::query()
                ->where('provider_slug', $provider->slug->value)
                ->where('is_enabled', true)
                ->update(['is_enabled' => false]);
        }

        $provider->load(['latestResult', 'latestSuccessfulResult']);

        return ProviderResource::make($provider->refresh())->additional([
            'success' => true,
            'code'    => 200,
            'message' => 'Provider updated successfully.',
        ]);
    }

    /**
     * Trigger an immediate manual speedtest run for a provider.
     *
     * Dispatches the run as a queued job and returns 202 Accepted. The job
     * is skipped automatically if the provider is under a maintenance window.
     *
     * @param Provider $provider
     */
    #[Endpoint(title: 'Run speedtest now', description: 'Trigger an immediate manual speedtest run for a provider. Dispatches the run as a queued job and returns 202 Accepted. The job is skipped automatically if the provider is under a maintenance window.')]
    public function runNow(Provider $provider): JsonResponse
    {
        abort_unless($provider->is_runnable, 422, 'Provider is disabled or not fully configured.');

        dispatch(new RunSpeedtestJob(provider: $provider))->onQueue(QueueWorkerName::Speedtest->value);

        return response()->json([
            'success' => true,
            'code'    => 202,
            'message' => "Manual speedtest run queued for {$provider->slug->label()}.",
            'data'    => [
                'provider_slug' => $provider->slug->value,
            ],
        ], 202);
    }
}
