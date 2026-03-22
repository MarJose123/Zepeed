<?php

namespace App\Http\Controllers;

use App\Enums\MaintenanceWindowType;
use App\Http\Requests\StoreMaintenanceWindowRequest;
use App\Http\Requests\UpdateMaintenanceWindowRequest;
use App\Http\Resources\MaintenanceWindowResource;
use App\Models\MaintenanceWindow;
use App\Services\InertiaNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceWindowController extends Controller
{
    public function index(): Response
    {
        $windows = MaintenanceWindow::query()
            ->orderByDesc('is_active')->latest()
            ->get();

        return Inertia::render('Settings/Schedules/Index', [
            'windows'     => MaintenanceWindowResource::collection($windows),
            'globalPause' => MaintenanceWindow::query()
                ->active()
                ->ofType(MaintenanceWindowType::Indefinite)
                ->whereJsonContains('providers', 'all')
                ->exists(),
        ]);
    }

    public function store(StoreMaintenanceWindowRequest $request): RedirectResponse
    {
        MaintenanceWindow::query()->create($request->validated());

        InertiaNotification::make()
            ->success()
            ->title('Window created')
            ->message('Maintenance window has been scheduled.')
            ->send();

        return redirect()->back();
    }

    public function update(
        UpdateMaintenanceWindowRequest $request,
        MaintenanceWindow $maintenanceWindow,
    ): RedirectResponse {
        $maintenanceWindow->update($request->validated());

        InertiaNotification::make()
            ->success()
            ->title('Window updated')
            ->message("Maintenance window \"{$maintenanceWindow->label}\" has been updated.")
            ->send();

        return redirect()->back();
    }

    public function destroy(MaintenanceWindow $maintenanceWindow): RedirectResponse
    {
        $label = $maintenanceWindow->label;
        $maintenanceWindow->delete();

        InertiaNotification::make()
            ->success()
            ->title('Window deleted')
            ->message("Maintenance window \"{$label}\" has been removed.")
            ->send();

        return redirect()->back();
    }

    /**
     * Toggle the global indefinite pause on/off.
     * Driven by the "Global pause" toggle on the Maintenance tab.
     */
    public function toggleGlobalPause(): RedirectResponse
    {
        $isCurrentlyActive = MaintenanceWindow::query()
            ->active()
            ->ofType(MaintenanceWindowType::Indefinite)
            ->whereJsonContains('providers', 'all')
            ->exists();

        MaintenanceWindow::toggleGlobalPause(! $isCurrentlyActive);

        InertiaNotification::make()
            ->warning()
            ->title($isCurrentlyActive ? 'Global pause deactivated' : 'Global pause activated')
            ->message(
                $isCurrentlyActive
                    ? 'All providers will resume their scheduled runs.'
                    : 'All speedtest runs are now suppressed.'
            )
            ->send();

        return redirect()->back();
    }
}
