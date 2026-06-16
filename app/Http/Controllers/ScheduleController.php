<?php

namespace App\Http\Controllers;

use App\Enums\MaintenanceWindowType;
use App\Http\Resources\MaintenanceWindowResource;
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
            static fn (MaintenanceWindow $w): bool => $w->type === MaintenanceWindowType::Indefinite
                && $w->coversAllProviders()
                && $w->label === 'Global pause'
        );

        // Group schedules by provider for accordion rendering
        $allSchedules = ProviderSchedule::query()
            ->oldest()
            ->get()
            ->groupBy(static fn (ProviderSchedule $schedule): string => $schedule->provider_slug->value);

        $providers = Provider::query()
            ->get()
            ->map(static function (Provider $provider) use ($allSchedules) {
                $providerSchedules = $allSchedules->get($provider->slug->value, collect());

                return [
                    'id'             => $provider->id,
                    'slug'           => $provider->slug->value,
                    'name'           => $provider->name,
                    'label'          => $provider->label,
                    'website_link'   => $provider->website_link,
                    'is_enabled'     => $provider->is_enabled,
                    'is_healthy'     => $provider->is_healthy,
                    'status_badge'   => $provider->status_badge,
                    'schedule_count' => $providerSchedules->count(),
                    'schedules'      => ProviderScheduleResource::collection(
                        $providerSchedules
                    )->resolve(),
                ];
            });

        return Inertia::render('settings/Schedules', [
            'providers' => $providers->all(),

            'windows' => MaintenanceWindowResource::collection(
                $windowList
            )->resolve(),

            // Stats for the maintenance tab dashboard bar
            'stats' => [
                'total'            => $windowList->count(),
                'currently_active' => $windowList
                    ->filter(static fn (MaintenanceWindow $w) => $w->isCurrentlyActive())
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
