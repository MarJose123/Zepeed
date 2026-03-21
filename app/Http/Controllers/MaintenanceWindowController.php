<?php

namespace App\Http\Controllers;

use App\Enums\MaintenanceWindowType;
use App\Http\Requests\StoreMaintenanceWindowRequest;
use App\Http\Requests\UpdateMaintenanceWindowRequest;
use App\Http\Resources\MaintenanceWindowResource;
use App\Models\MaintenanceWindow;
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

        return back()
            ->with('success', 'Maintenance window created.');
    }

    public function update(
        UpdateMaintenanceWindowRequest $request,
        MaintenanceWindow $maintenanceWindow,
    ): RedirectResponse {
        $maintenanceWindow->update($request->validated());

        return back()
            ->with('success', 'Maintenance window updated.');
    }

    public function destroy(MaintenanceWindow $maintenanceWindow): RedirectResponse
    {
        $maintenanceWindow->delete();

        return back()
            ->with('success', 'Maintenance window deleted.');
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

        return back()
            ->with('success', $isCurrentlyActive ? 'Global pause deactivated.' : 'Global pause activated.');
    }
}
