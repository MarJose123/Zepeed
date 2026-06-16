<?php

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
use Illuminate\Http\Request;
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
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/GeneralSettings', [
            'settings'              => Setting::generalSettings(),
            'stats'                 => $this->updateAction->stats(),
            'scheduler_jobs'        => $this->updateAction->schedulerJobs(),
            'storage_tables'        => $this->updateAction->storageTables(),
            'downtime_logs'         => $this->updateAction->downtimeLogs(
                page: max(1, (int) $request->query('downtime_page', 1)),
            ),
            'retention_projections' => $this->updateAction->retentionProjections(),
            'timezones'             => DateTimeZone::listIdentifiers(),
        ]);
    }

    /**
     * Persist general settings, apply boot-time side-effects, and handle
     * the maintenance mode bypass redirect correctly.
     *
     * Laravel's bypass mechanism works by visiting /{secret} as a PATH
     * segment — NOT as a query string. That request sets the
     * laravel_maintenance cookie and redirects to /.
     *
     * Because we exempt the settings route from maintenance mode above,
     * the admin can always return directly to settings after the cookie
     * is set without needing a second redirect chain.
     */
    public function update(GeneralSettingsData $data): RedirectResponse
    {
        try {
            $maintenanceWasActive = (bool) Setting::get('maintenance_enabled', false);

            $this->updateAction->handle($data);

            $maintenanceNowActive = $data->maintenance_enabled;

            // Maintenance was just toggled ON — redirect through the bypass
            // URL so the laravel_maintenance cookie is set in the admin's
            // browser. Because the settings route is in the $except list,
            // the admin can navigate back immediately after.
            if (! $maintenanceWasActive && $maintenanceNowActive) {
                $secret = (string) Setting::get('bypass_secret', '');

                if ($secret !== '') {
                    InertiaNotification::make()
                        ->success()
                        ->message('Maintenance mode enabled. Others user will be redirected to the service unavailable page.')
                        ->send();

                    redirect()->setIntendedUrl(route('speedtest.general-settings.edit'));

                    // /{secret} is the Laravel-recognised bypass path that
                    // issues the laravel_maintenance cookie then redirects to /.
                    return redirect(url("/{$secret}"));
                }
            }

            InertiaNotification::make()
                ->success()
                ->message(
                    $maintenanceNowActive
                        ? 'Settings saved. Maintenance mode is active.'
                        : 'Settings saved successfully.'
                )
                ->send();
        } catch (Throwable $e) {
            report($e);

            InertiaNotification::make()
                ->error()
                ->message('Failed to save settings: ' . $e->getMessage())
                ->send();
        }

        return back();
    }

    /**
     * Clear the specified application cache type.
     *
     * Supported: app | config | route | view
     */
    public function clearCache(ClearCacheRequest $request): RedirectResponse
    {
        /** @var string $type */
        $type = $request->validated('type');

        try {
            $this->cacheAction->handle($type);

            $label = match ($type) {
                'optimize'       => 'Application optimized',
                'optimize:clear' => 'Cache cleared',
                default          => ucfirst($type),
            };

            InertiaNotification::make()
                ->success()
                ->message("{$label} successfully.")
                ->send();
        } catch (Throwable $e) {
            report($e);

            InertiaNotification::make()
                ->error()
                ->message('Failed: ' . $e->getMessage())
                ->send();
        }

        return back();
    }

    /**
     * Execute a destructive danger-zone operation.
     *
     * Supported: clear_results | clear_log | reset_config | factory_reset
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
                ->message('Operation failed: ' . $e->getMessage())
                ->send();
        }

        return back();
    }
}
