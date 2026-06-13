<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePingTargetRequest;
use App\Http\Requests\UpdatePingTargetRequest;
use App\Http\Resources\PingTargetResource;
use App\Jobs\RunPingTestJob;
use App\Models\PingTarget;
use App\Services\InertiaNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PingTargetController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('network/PingTargets', [
            'targets' => PingTargetResource::collection(
                PingTarget::query()->latest()->get()
            )->resolve(),
        ]);
    }

    public function store(StorePingTargetRequest $request): RedirectResponse
    {
        $target = PingTarget::query()->create($request->validated());

        InertiaNotification::make()
            ->success()
            ->title('Ping target added')
            ->message("\"{$target->label}\" is now being monitored.")
            ->send();

        return back();
    }

    public function update(UpdatePingTargetRequest $request, PingTarget $pingTarget): RedirectResponse
    {
        $pingTarget->update($request->validated());

        InertiaNotification::make()
            ->success()
            ->title('Ping target updated')
            ->message("\"{$pingTarget->label}\" has been updated.")
            ->send();

        return back();
    }

    public function destroy(PingTarget $pingTarget): RedirectResponse
    {
        $label = $pingTarget->label;
        $pingTarget->delete();

        InertiaNotification::make()
            ->success()
            ->title('Ping target deleted')
            ->message("\"{$label}\" has been removed.")
            ->send();

        return to_route('speedtest.network.ping-targets.index');
    }

    public function runNow(PingTarget $pingTarget): RedirectResponse
    {
        dispatch(new RunPingTestJob($pingTarget));

        InertiaNotification::make()
            ->info()
            ->title('Ping test queued')
            ->message("Testing \"{$pingTarget->label}\"...")
            ->send();

        return back();
    }
}
