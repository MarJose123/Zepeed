<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProviderScheduleRequest;
use App\Http\Requests\UpdateProviderScheduleRequest;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Services\InertiaNotification;
use Illuminate\Http\RedirectResponse;

class ProviderScheduleController extends Controller
{
    public function store(StoreProviderScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $providerSchedule = ProviderSchedule::query()->create($data);

        InertiaNotification::make()
            ->success()
            ->title('Schedule created')
            ->message("Schedule \"{$data['label']}\" has been created.")
            ->send();

        return back();
    }

    public function update(
        UpdateProviderScheduleRequest $request,
        ProviderSchedule $providerSchedule,
    ): RedirectResponse {
        $data = $request->validated();

        $wasEnabled = $providerSchedule->is_enabled;

        $providerSchedule->update($data);

        InertiaNotification::make()
            ->success()
            ->title('Schedule updated')
            ->message("\"{$providerSchedule->label}\" schedule has been saved.")
            ->send();

        $hasCron = filled($providerSchedule->cron_expression);
        $scheduleDisabled = ! $providerSchedule->is_enabled;
        $providerDisabled = $providerSchedule->provider instanceof Provider
            && ! $providerSchedule->provider->is_enabled;

        if ($hasCron || $providerSchedule->is_enabled) {
            if ($scheduleDisabled && $providerDisabled) {
                InertiaNotification::make()
                    ->warning()
                    ->title('Schedule will not run')
                    ->message(
                        "Both the schedule and the {$providerSchedule->provider_slug->label()} ".
                        'provider are disabled. Enable both to start running.'
                    )
                    ->send();
            } elseif ($scheduleDisabled) {
                InertiaNotification::make()
                    ->warning()
                    ->title('Schedule is disabled')
                    ->message(
                        "\"{$providerSchedule->label}\" has a cron expression set ".
                        'but is disabled. Enable it to start running.'
                    )
                    ->send();
            } elseif ($providerDisabled && ! $wasEnabled && $providerSchedule->is_enabled) {
                InertiaNotification::make()
                    ->warning()
                    ->title("{$providerSchedule->provider_slug->label()} provider is disabled")
                    ->message(
                        'The schedule is active but will not run until '.
                        "{$providerSchedule->provider_slug->label()} is enabled in Speedtest Providers."
                    )
                    ->send();
            }
        }

        return back();
    }

    public function destroy(ProviderSchedule $providerSchedule): RedirectResponse
    {
        $label = $providerSchedule->label;
        $providerSchedule->delete();

        InertiaNotification::make()
            ->success()
            ->title('Schedule deleted')
            ->message("Schedule \"{$label}\" has been removed.")
            ->send();

        return back();
    }
}
