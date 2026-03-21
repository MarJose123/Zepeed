<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProviderScheduleRequest;
use App\Models\ProviderSchedule;
use Illuminate\Http\RedirectResponse;

class ProviderScheduleController extends Controller
{
    public function update(
        UpdateProviderScheduleRequest $request,
        ProviderSchedule $providerSchedule,
    ): RedirectResponse {
        $providerSchedule->update($request->validated());

        return back()
            ->with('success', 'Schedule updated.');
    }
}
