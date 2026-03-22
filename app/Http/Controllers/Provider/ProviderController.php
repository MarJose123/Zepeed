<?php

namespace App\Http\Controllers\Provider;

use App\Enums\QueueWorkerName;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Jobs\RunSpeedtestJob;
use App\Models\Provider;
use App\Services\InertiaNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProviderController extends Controller
{
    /**
     * Render the Providers settings page.
     * Passes all 3 providers to Vue; tab switching is client-side only.
     */
    public function index(): Response
    {
        return Inertia::render('settings/Providers', [
            'providers' => ProviderResource::collection(
                Provider::query()->orderBy('id')->get()
            )->resolve(),
        ]);
    }

    /**
     * Update a single provider's configuration.
     */
    public function update(UpdateProviderRequest $request, Provider $provider): RedirectResponse
    {
        $provider->update($request->validated());

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

        dispatch(new RunSpeedtestJob(provider: $provider, testOnly: true))->onQueue(QueueWorkerName::Speedtest->value);

        InertiaNotification::make()
            ->success()
            ->title('Run queued')
            ->message("Manual run dispatched for {$provider->slug->label()}. Please stay on this page until the test completes.")
            ->send();

        return to_route('speedtest.server.providers.index');
    }
}
