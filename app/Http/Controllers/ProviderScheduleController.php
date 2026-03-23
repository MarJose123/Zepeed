<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProviderScheduleRequest;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use App\Services\InertiaNotification;
use Illuminate\Http\RedirectResponse;

class ProviderScheduleController extends Controller
{
    public function update(
        UpdateProviderScheduleRequest $request,
        ProviderSchedule $providerSchedule,
    ): RedirectResponse {
        $providerSchedule->update($request->validated());

        InertiaNotification::make()
            ->success()
            ->title('Schedule updated')
            ->message("{$providerSchedule->provider_slug->label()} schedule has been saved.")
            ->send();

        // Warn if cron expression is set but the provider itself is disabled
        $hasCron = filled($providerSchedule->cron_expression);
        $scheduleDisabled = ! $providerSchedule->is_enabled;
        $providerDisabled = $providerSchedule->provider instanceof Provider
            && ! $providerSchedule->provider->is_enabled;

        // Only warn if there is something configured that won't run
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
                        "{$providerSchedule->provider_slug->label()} has a cron expression set ".
                        'but the schedule is disabled. Enable the schedule to start running.'
                    )
                    ->send();
            } elseif ($providerDisabled) {
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
}
