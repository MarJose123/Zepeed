<?php

declare(strict_types=1);

namespace App\Actions\App;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ExecuteDangerAction
{
    private const array ALLOWED = [
        'clear_results',
        'clear_log',
        'reset_config',
        'factory_reset',
    ];

    /**
     * Dispatch the requested destructive operation.
     *
     * @throws InvalidArgumentException
     */
    public function handle(string $action): void
    {
        if (! in_array($action, self::ALLOWED, true)) {
            throw new InvalidArgumentException("Unknown danger action: {$action}");
        }

        match ($action) {
            'clear_results'  => self::clearResults(),
            'clear_log'      => self::clearLog(),
            'reset_config'   => $this->resetConfig(),
            'factory_reset'  => $this->factoryReset(),
        };
    }

    /**
     * Truncate all speedtest result rows.
     */
    private static function clearResults(): void
    {
        DB::table('speed_results')->truncate();
    }

    /**
     * Truncate all webhook delivery log rows.
     */
    private static function clearLog(): void
    {
        DB::table('webhook_deliveries')->truncate();
    }

    /**
     * Remove all configuration data while preserving speed results and users.
     */
    private function resetConfig(): void
    {
        DB::transaction(static function (): void {
            foreach (['providers', 'alert_rules', 'mail_providers', 'webhooks', 'email_templates', 'schedules', 'maintenance_windows', 'settings'] as $table) {
                DB::table($table)->truncate();
            }
        });
    }

    /**
     * Factory reset: truncate every table except users, then re-seed.
     */
    private function factoryReset(): void
    {
        $protected = ['users', 'password_reset_tokens', 'sessions'];

        DB::transaction(static function () use ($protected): void {
            $tables = DB::select('SHOW TABLES');
            $dbKey = 'Tables_in_' . config('database.connections.' . config('database.default') . '.database');

            foreach ($tables as $row) {
                $table = (array) $row;
                $name = $table[$dbKey] ?? '';

                if ($name !== '' && ! in_array($name, $protected, true)) {
                    DB::table($name)->truncate();
                }
            }
        });

        Artisan::call('db:seed', ['--force' => true]);
    }
}
