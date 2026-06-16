<?php

namespace App\Http\Controllers\Provider;

use App\Enums\QueueWorkerName;
use App\Enums\SpeedtestServer;
use App\Events\Speedtest\Test\SpeedtestTestCancelledEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\OoklaServerSearchRequest;
use App\Http\Requests\UpdateProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Http\Resources\ProviderScheduleResource;
use App\Jobs\RunSpeedtestJob;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Models\SpeedtestTestSession;
use App\Models\User;
use App\Services\InertiaNotification;
use App\Services\OoklaServerSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderController extends Controller
{
    /**
     * Render the Providers settings page.
     *
     * Passes all providers and a schedulesMap keyed by provider slug.
     * Only enabled schedules with a cron expression are included —
     * these are the ones that will stop running when a provider is disabled.
     */
    public function index(): Response
    {
        $providers = Provider::query()->orderBy('id')->get();

        /** @var array<string, list<array<string, mixed>>> $schedulesMap */
        $schedulesMap = [];

        foreach (SpeedtestServer::cases() as $server) {
            $schedulesMap[$server->value] = ProviderScheduleResource::collection(
                ProviderSchedule::enabledForProvider($server)
                    ->filter(static fn (ProviderSchedule $s): bool => filled($s->cron_expression))
                    ->values()
            )->resolve();
        }

        return Inertia::render('settings/Providers', [
            'providers'    => ProviderResource::collection($providers)->resolve(),
            'schedulesMap' => $schedulesMap,
        ]);
    }

    /**
     * Update a single provider's configuration.
     *
     * When a previously enabled provider is disabled, all its associated
     * schedules are also disabled so they do not linger as orphaned active entries.
     */
    public function update(UpdateProviderRequest $request, Provider $provider): RedirectResponse
    {
        $wasEnabled = $provider->is_enabled;
        $willDisable = $wasEnabled && ! $request->boolean('is_enabled');

        $provider->update($request->validated());

        if ($willDisable) {
            $disabledCount = ProviderSchedule::query()
                ->where('provider_slug', $provider->slug->value)
                ->where('is_enabled', true)
                ->update(['is_enabled' => false]);

            InertiaNotification::make()
                ->success()
                ->title('Provider disabled')
                ->message("{$provider->slug->label()} has been disabled.")
                ->send();

            if ($disabledCount > 0) {
                InertiaNotification::make()
                    ->warning()
                    ->title(
                        $disabledCount === 1
                            ? '1 schedule disabled'
                            : "{$disabledCount} schedules disabled"
                    )
                    ->message(
                        $disabledCount === 1
                            ? "1 active schedule for {$provider->slug->label()} has been disabled."
                            : "{$disabledCount} active schedules for {$provider->slug->label()} have been disabled."
                    )
                    ->send();
            }

            return to_route('speedtest.server.providers.index');
        }

        InertiaNotification::make()
            ->success()
            ->title('Provider updated')
            ->message("{$provider->slug->label()} settings have been saved.")
            ->send();

        return to_route('speedtest.server.providers.index');
    }

    /**
     * Trigger an immediate manual run for a provider.
     * Dispatches the job directly, bypassing the scheduler.
     */
    public function runNow(Provider $provider): RedirectResponse
    {
        abort_unless($provider->is_runnable, 422, 'Provider is disabled or not fully configured.');

        dispatch(new RunSpeedtestJob(provider: $provider))->onQueue(QueueWorkerName::Speedtest->value);

        InertiaNotification::make()
            ->success()
            ->title('Run queued')
            ->message("Manual run dispatched for {$provider->slug->label()}.")
            ->send();

        return to_route('speedtest.server.providers.index');
    }

    /**
     * Start an async test run for a provider.
     *
     * Returns 202 Accepted immediately. The test runs as a queued job
     * and broadcasts WebSocket events to the frontend via the private
     * channel speedtest.test.{providerSlug}.
     */
    public function test(Request $request, Provider $provider): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $session = SpeedtestTestSession::query()->create([
            'provider_id' => $provider->id,
            'user_id'     => $user->id,
            'status'      => 'pending',
        ]);

        dispatch(new RunSpeedtestJob(
            provider      : $provider,
            testOnly      : true,
            testSessionId : $session->id,
        ))->onQueue(QueueWorkerName::Speedtest->value);

        return response()->json([
            'test_session_id' => $session->id,
            'provider_slug'   => $provider->slug->value,
        ], 202);
    }

    /**
     * Cancel an in-progress or pending test session.
     *
     * Marks the session as cancelled so the job abandons execution
     * if it has not started yet, and broadcasts the cancellation event
     * so the frontend can update immediately.
     */
    public function cancelTest(Request $request, Provider $provider, string $testSessionId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var SpeedtestTestSession $session */
        $session = SpeedtestTestSession::query()
            ->where('id', $testSessionId)
            ->where('user_id', $user->id)
            ->where('provider_id', $provider->id)
            ->firstOrFail();

        if (in_array($session->status, ['completed', 'failed', 'cancelled', 'skipped'], true)) {
            return response()->json(['message' => 'Test has already finished.'], 422);
        }

        $session->markCancelled();

        event(new SpeedtestTestCancelledEvent($provider));

        return response()->json(['message' => 'Test cancelled.']);
    }

    /**
     * Search for Ookla speedtest servers.
     *
     * Results are drawn from a 24-hour server-side cache. On a cache miss
     * the Ookla API is called once, the full list stored, then filtered.
     * Subsequent requests within the TTL are served instantly from cache.
     */
    public function searchOoklaServers(
        OoklaServerSearchRequest $request,
        OoklaServerSearchService $service,
    ): JsonResponse {
        $query = (string) $request->string('q')->trim();
        $results = $service->search($query);

        return response()->json([
            'results' => $results,
            'count'   => $results->count(),
        ]);
    }

    /**
     * Bust the Ookla server list cache.
     *
     * Intended as an escape hatch when the cached list is stale.
     * The fresh list is fetched from the API immediately and re-cached.
     */
    public function refreshOoklaServersCache(OoklaServerSearchService $service): JsonResponse
    {
        $servers = $service->refreshCache();

        return response()->json([
            'message' => 'Ookla server cache refreshed.',
            'count'   => $servers->count(),
        ]);
    }
}
