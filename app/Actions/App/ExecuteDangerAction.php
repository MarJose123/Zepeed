<?php

namespace App\Actions\App;

use App\Models\SpeedResult;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        SpeedResult::query()->truncate();
    }

    /**
     * Truncate all webhook delivery log rows.
     */
    private static function clearLog(): void
    {
        WebhookDelivery::query()->truncate();
    }

    /**
     * Remove all configuration data while preserving speed results and users.
     *
     * NOTE: table names here are intentionally a fixed list rather than
     * model classes — some (e.g. 'schedules') don't map 1:1 to a model's
     * default table name, and changing that mapping is outside the scope
     * of this refactor. truncate() itself has no database-specific syntax,
     * so this loop is already fully portable across MySQL/MariaDB/Postgres.
     */
    private function resetConfig(): void
    {
        DB::transaction(static function (): void {
            Schema::disableForeignKeyConstraints();

            foreach (['providers', 'alert_rules', 'mail_providers', 'webhooks', 'email_templates', 'schedules', 'maintenance_windows', 'settings'] as $table) {
                DB::table($table)->truncate();
            }

            Schema::enableForeignKeyConstraints();
        });
    }

    /**
     * Factory reset: truncate every table except users, then re-seed.
     *
     * Schema::getTables() replaces the previous `SHOW TABLES` raw query,
     * which only worked on MySQL/MariaDB — getTables() is implemented
     * natively for MySQL, MariaDB, PostgreSQL, and SQLite.
     */
    private function factoryReset(): void
    {
        $protected = ['users', 'password_reset_tokens', 'sessions'];

        DB::transaction(static function () use ($protected): void {
            Schema::disableForeignKeyConstraints();

            foreach (Schema::getTables() as $table) {
                $name = $table['name'] ?? '';

                if ($name !== '' && ! in_array($name, $protected, true)) {
                    DB::table($name)->truncate();
                }
            }

            Schema::enableForeignKeyConstraints();
        });

        Artisan::call('db:seed', ['--force' => true]);
    }
}
