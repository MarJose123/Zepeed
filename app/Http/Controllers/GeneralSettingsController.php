<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\App\ClearCacheAction;
use App\Actions\App\ExecuteDangerAction;
use App\Actions\App\UpdateGeneralSettings;
use App\Data\GeneralSettingsData;
use App\Http\Requests\ClearCacheRequest;
use App\Http\Requests\DangerActionRequest;
use App\Models\Setting;
use App\Services\InertiaNotification;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class GeneralSettingsController extends Controller
{
    public function __construct(
        private readonly UpdateGeneralSettings $updateAction,
        private readonly ExecuteDangerAction $dangerAction,
        private readonly ClearCacheAction $cacheAction,
    ) {}

    /**
     * Render the General Settings page with all supporting data.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/General', [
            'settings'              => Setting::generalSettings(),
            'stats'                 => $this->updateAction->stats(),
            'scheduler_jobs'        => $this->updateAction->schedulerJobs(),
            'storage_tables'        => $this->updateAction->storageTables(),
            'downtime_logs'         => $this->updateAction->downtimeLogs(),
            'retention_projections' => $this->updateAction->retentionProjections(),
            'timezones'             => DateTimeZone::listIdentifiers(),
        ]);
    }

    /**
     * Persist general settings and sync maintenance mode side-effects.
     */
    public function update(GeneralSettingsData $data): RedirectResponse
    {
        try {
            $this->updateAction->handle($data);

            InertiaNotification::make()
                ->success()
                ->message('Settings saved successfully.')
                ->send();
        } catch (Throwable $e) {
            report($e);

            InertiaNotification::make()
                ->error()
                ->message('Failed to save settings: '.$e->getMessage())
                ->send();
        }

        return back();
    }

    /**
     * Clear the specified application cache type.
     *
     * Supported types: app | config | route | view
     */
    public function clearCache(ClearCacheRequest $request): RedirectResponse
    {
        /** @var string $type */
        $type = $request->validated('type');

        try {
            $this->cacheAction->handle($type);

            $label = match ($type) {
                'app'    => 'Application',
                'config' => 'Config',
                'route'  => 'Route',
                'view'   => 'View',
                default  => ucfirst($type),
            };

            InertiaNotification::make()
                ->success()
                ->message("{$label} cache cleared successfully.")
                ->send();
        } catch (Throwable $e) {
            report($e);

            InertiaNotification::make()
                ->error()
                ->message('Failed to clear cache: '.$e->getMessage())
                ->send();
        }

        return back();
    }

    /**
     * Execute a destructive danger-zone operation.
     *
     * Supported actions: clear_results | clear_log | reset_config | factory_reset
     */
    public function danger(DangerActionRequest $request): RedirectResponse
    {
        /** @var string $action */
        $action = $request->validated('action');

        try {
            $this->dangerAction->handle($action);

            $message = match ($action) {
                'clear_results' => 'Speed results cleared successfully.',
                'clear_log'     => 'Webhook delivery log cleared successfully.',
                'reset_config'  => 'Configuration reset successfully.',
                'factory_reset' => 'Factory reset completed successfully.',
                default         => 'Operation completed successfully.',
            };

            InertiaNotification::make()
                ->success()
                ->message($message)
                ->send();
        } catch (Throwable $e) {
            report($e);

            InertiaNotification::make()
                ->error()
                ->message('Operation failed: '.$e->getMessage())
                ->send();
        }

        return back();
    }
}
