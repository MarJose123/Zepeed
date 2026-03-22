<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProviderScheduleRequest;
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

        return back();
    }
}
