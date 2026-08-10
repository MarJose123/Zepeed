<?php

namespace App\Enums;

/**
 * Token abilities grant fine-grained access to the REST API.
 *
 * Each module exposes a `:view` ability for read operations and action-specific
 * abilities (`:create`, `:update`, `:delete`, ...) for mutations. Any write
 * ability implicitly grants `:view` for the same module (enforced with Sanctum's
 * `ability` middleware on read routes and `abilities` middleware on write routes).
 */
enum TokenAbility: string
{
    case AlertsView = 'alerts:view';
    case AlertsCreate = 'alerts:create';
    case AlertsUpdate = 'alerts:update';
    case AlertsDelete = 'alerts:delete';

    case AppView = 'app:view';

    case AppriseView = 'apprise:view';
    case AppriseCreate = 'apprise:create';
    case AppriseUpdate = 'apprise:update';
    case AppriseDelete = 'apprise:delete';
    case AppriseTest = 'apprise:test';

    case ExportsView = 'exports:view';
    case ExportsCreate = 'exports:create';

    case MaintenanceView = 'maintenance:view';
    case MaintenanceCreate = 'maintenance:create';
    case MaintenanceUpdate = 'maintenance:update';
    case MaintenanceDelete = 'maintenance:delete';

    case PingAlertsView = 'ping-alerts:view';
    case PingAlertsCreate = 'ping-alerts:create';
    case PingAlertsUpdate = 'ping-alerts:update';
    case PingAlertsDelete = 'ping-alerts:delete';

    case PingResultsView = 'ping-results:view';

    case ProvidersView = 'providers:view';
    case ProvidersUpdate = 'providers:update';

    case SchedulesView = 'schedules:view';
    case SchedulesCreate = 'schedules:create';
    case SchedulesUpdate = 'schedules:update';
    case SchedulesDelete = 'schedules:delete';

    case SpeedtestView = 'speedtest:view';
    case SpeedtestRun = 'speedtest:run';

    case WebhooksView = 'webhooks:view';
    case WebhooksCreate = 'webhooks:create';
    case WebhooksUpdate = 'webhooks:update';
    case WebhooksDelete = 'webhooks:delete';
    case WebhooksTest = 'webhooks:test';

    /**
     * Display name of the module this ability belongs to.
     */
    public function module(): string
    {
        return match ($this) {
            self::AlertsView, self::AlertsCreate, self::AlertsUpdate, self::AlertsDelete                             => 'Alerts',
            self::AppView                                                                                            => 'App',
            self::AppriseView, self::AppriseCreate, self::AppriseUpdate, self::AppriseDelete, self::AppriseTest      => 'Apprise',
            self::ExportsView, self::ExportsCreate                                                                   => 'Exports',
            self::MaintenanceView, self::MaintenanceCreate, self::MaintenanceUpdate, self::MaintenanceDelete         => 'Maintenance',
            self::PingAlertsView, self::PingAlertsCreate, self::PingAlertsUpdate, self::PingAlertsDelete             => 'Ping Alerts',
            self::PingResultsView                                                                                    => 'Ping Results',
            self::ProvidersView, self::ProvidersUpdate                                                               => 'Providers',
            self::SchedulesView, self::SchedulesCreate, self::SchedulesUpdate, self::SchedulesDelete                 => 'Schedules',
            self::SpeedtestView, self::SpeedtestRun                                                                  => 'Speedtest',
            self::WebhooksView, self::WebhooksCreate, self::WebhooksUpdate, self::WebhooksDelete, self::WebhooksTest => 'Webhooks',
        };
    }

    /**
     * The kind of access this ability grants: view, create, update,
     * delete, test, or run.
     */
    public function kind(): string
    {
        return match (true) {
            str_ends_with($this->value, ':view')   => 'view',
            str_ends_with($this->value, ':create') => 'create',
            str_ends_with($this->value, ':update') => 'update',
            str_ends_with($this->value, ':delete') => 'delete',
            str_ends_with($this->value, ':test')   => 'test',
            default                                => 'run',
        };
    }

    /**
     * Human-readable label for the ability kind.
     */
    public function label(): string
    {
        return ucfirst($this->kind());
    }

    /**
     * Abilities grouped by module, ordered alphabetically by module name.
     * Used to render the ability picker in the token creation UI.
     *
     * @return array<int, array{module: string, abilities: array<int, array{value: string, kind: string, label: string}>}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->sortBy(static fn (self $ability): string => $ability->module())
            ->groupBy(static fn (self $ability): string => $ability->module())
            ->map(static fn ($group, string $module): array => [
                'module'    => $module,
                'abilities' => $group->map(static fn (self $ability): array => [
                    'value' => $ability->value,
                    'kind'  => $ability->kind(),
                    'label' => $ability->label(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * All ability values as plain strings.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
