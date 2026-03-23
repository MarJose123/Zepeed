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
        return Inertia::render('settings/Schedules', [
            'providers'  => ProviderResource::collection(Provider::all())->resolve(),
            'schedules'  => ProviderScheduleResource::collection(
                ProviderSchedule::query()->orderBy('provider_slug')->get()
            )->resolve(),
            'windows' => MaintenanceWindowResource::collection(
                MaintenanceWindow::query()
                    ->orderByDesc('is_active')->latest()
                    ->get()
            )->resolve(),
            'globalPause' => MaintenanceWindow::query()
                ->active()
                ->ofType(MaintenanceWindowType::Indefinite)
                ->whereJsonContains('providers', 'all')
                ->exists(),
        ]);
    }
}
