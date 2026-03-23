<?php

namespace App\Http\Controllers;

use App\Enums\MaintenanceWindowType;
use App\Http\Resources\MaintenanceWindowResource;
use App\Http\Resources\ProviderResource;
use App\Http\Resources\ProviderScheduleResource;
use App\Models\MaintenanceWindow;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(): Response
    {
        $windows = MaintenanceWindow::query()
            ->orderByDesc('is_active')
            ->latest()
            ->get();

        // Exclude the global pause window from the list —
        // it is controlled separately via the toggle
        $windowList = $windows->reject(
            fn (MaintenanceWindow $w): bool => $w->type === MaintenanceWindowType::Indefinite
            && $w->coversAllProviders()
            && $w->label === 'Global pause'
        );

        return Inertia::render('settings/Schedules', [
            'providers' => ProviderResource::collection(
                Provider::all()
            )->resolve(),

            'schedules' => ProviderScheduleResource::collection(
                ProviderSchedule::query()->oldest()->get()
            )->resolve(),

            'windows' => MaintenanceWindowResource::collection(
                $windowList
            )->resolve(),

            // Stats for the maintenance tab dashboard bar
            'stats' => [
                'total'            => $windowList->count(),
                'currently_active' => $windowList
                    ->filter(fn (MaintenanceWindow $w) => $w->isCurrentlyActive())
                    ->count(),
            ],

            'globalPause' => MaintenanceWindow::query()
                ->active()
                ->ofType(MaintenanceWindowType::Indefinite)
                ->whereJsonContains('providers', 'all')
                ->exists(),
        ]);
    }
}
