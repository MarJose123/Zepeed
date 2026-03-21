<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Jobs\RunSpeedtestJob;
use App\Models\Provider;
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

        return back()
            ->with('success', "{$provider->name} settings saved.");
    }

    /**
     * Trigger an immediate manual run for a provider.
     * Dispatches the job directly, bypassing the scheduler.
     */
    public function runNow(Provider $provider): RedirectResponse
    {
        abort_unless($provider->is_enabled, 422, 'Provider is disabled.');

        dispatch(new RunSpeedtestJob($provider));

        return back()
            ->with('success', "Manual run queued for {$provider->name}.");
    }
}
