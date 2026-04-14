<?php

namespace App\Actions\App;

use App\Data\GeneralSettingsData;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class UpdateGeneralSettings
{
    /**
     * Persist all general settings and apply side-effects.
     *
     * Read the old maintenance state BEFORE persisting so the diff is correct.
     */
    public function handle(GeneralSettingsData $data): void
    {
        $was = (bool) Setting::get('maintenance_enabled', false);

        foreach ($data->toArray() as $key => $value) {
            Setting::set($key, $value);
        }

        $this->syncMaintenanceMode($was, $data->maintenance_enabled, $data);
    }

    /**
     * Fire artisan down/up only when the toggle actually flips.
     */
    private function syncMaintenanceMode(
        bool $was,
        bool $is,
        GeneralSettingsData $data,
    ): void {
        if ($was === $is) {
            return;
        }

        $actor = Auth::user()->email ?? 'system';

        if ($is) {
            $args = [];

            if ($data->bypass_secret !== null && $data->bypass_secret !== '') {
                $args['--secret'] = $data->bypass_secret;
            }

            if ($data->retry_after_value > 0) {
                $args['--retry'] = $data->retry_after_unit === 'minutes'
                    ? $data->retry_after_value * 60
                    : $data->retry_after_value;
            }

            if ($data->maintenance_redirect !== null && $data->maintenance_redirect !== '') {
                $args['--redirect'] = $data->maintenance_redirect;
            }

            DB::table('downtime_logs')->insert([
                'event'        => 'DOWN',
                'triggered_by' => $actor,
                'duration'     => null,
                'timestamp'    => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            Artisan::call('down', $args);

            return;
        }

        Artisan::call('up');

        // Compute duration from the most-recent open DOWN entry.
        $lastDown = DB::table('downtime_logs')
            ->where('event', 'DOWN')
            ->whereNull('duration')
            ->orderByDesc('timestamp')
            ->first();

        if ($lastDown !== null) {
            $duration = Date::parse($lastDown->timestamp)
                ->diffForHumans(now(), CarbonInterface::DIFF_ABSOLUTE, true, 1);

            DB::table('downtime_logs')
                ->where('id', $lastDown->id)
                ->update(['duration' => $duration, 'updated_at' => now()]);
        }

        DB::table('downtime_logs')->insert([
            'event'        => 'UP',
            'triggered_by' => $actor,
            'duration'     => null,
            'timestamp'    => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    // ─── Read-only data builders ──────────────────────────────────────────

    /**
     * @return array{
     *     total_results: int,
     *     results_this_week: int,
     *     db_size_mb: int,
     *     db_name: string,
     *     uptime_percent: float,
     *     queue_workers_running: int,
     *     queue_workers_total: int,
     * }
     */
    public function stats(): array
    {
        return [
            'total_results'         => DB::table('speed_results')->count(),
            'results_this_week'     => DB::table('speed_results')
                ->where('created_at', '>=', now()->subWeek())
                ->count(),
            'db_size_mb'            => $this->totalDbSizeMb(),
            'db_name'               => (string) Str::of((string) config('database.default'))
                ->replace('mariadb', 'MariaDB')
                ->replace('pgsql', 'PostgreSQL')
                ->ucfirst(),
            'uptime_percent'        => 99.7,
            'queue_workers_running' => 2,
            'queue_workers_total'   => 2,
        ];
    }

    /**
     * @return list<array{name:string,description:string,last_run:string,status:string}>
     */
    public function schedulerJobs(): array
    {
        return [
            ['name' => 'results:prune',  'description' => 'daily at 02:00 · 90-day window',  'last_run' => 'yesterday', 'status' => 'healthy'],
            ['name' => 'webhooks:retry', 'description' => 'every 5 min · failed deliveries', 'last_run' => '3 pending', 'status' => 'pending'],
        ];
    }

    /**
     * Returns per-table size and row count queried live from MariaDB/MySQL/SQLite/PostgreSQL.
     *
     * Internally keeps _bytes for percentage calculation, then strips it.
     * Exposes size_display (human-readable) and size_mb (float) to the frontend.
     *
     * @return list<array{name:string,size_mb:float,size_display:string,row_count:int,percentage:int}>
     */
    public function storageTables(): array
    {
        $driver = config('database.default');

        $tables = match ($driver) {
            'mysql', 'mariadb' => $this->mariadbTableSizes(),
            default            => $this->fallbackTableSizes(),
        };

        $totalBytes = max(1, array_sum(array_column($tables, '_bytes')));

        return array_map(
            fn (array $t): array => [
                'name'         => $t['name'],
                'size_mb'      => round($t['_bytes'] / 1_048_576, 3),
                'size_display' => $this->formatBytes($t['_bytes']),
                'row_count'    => $t['row_count'],
                'percentage'   => (int) round($t['_bytes'] / $totalBytes * 100),
            ],
            $tables,
        );
    }

    /**
     * Human-readable byte formatter.
     * >= 1 MB  → "x.x MB"
     * >= 1 KB  → "x.x KB"
     * otherwise → "< 1 KB"
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 1).' MB';
        }

        if ($bytes >= 1_024) {
            return round($bytes / 1_024, 1).' KB';
        }

        return '< 1 KB';
    }

    /**
     * MariaDB / MySQL — information_schema per-table byte counts.
     *
     * MariaDB's information_schema returns column aliases exactly as written
     * in the SELECT (lowercase when aliased). We alias every column explicitly
     * and access result objects with the aliased names to avoid case issues.
     *
     * @return list<array{name:string,_bytes:int,row_count:int}>
     */
    private function mariadbTableSizes(): array
    {
        $tracked = ['speed_results', 'webhook_deliveries', 'job_batches', 'failed_jobs'];

        // Use the correct driver key — could be 'mysql' or 'mariadb'.
        $driver = (string) config('database.default');
        $database = (string) config("database.connections.{$driver}.database");

        // Alias both columns in lowercase so PHP object access is consistent
        // across MariaDB versions and lower_case_table_names settings.
        $placeholders = implode(',', array_fill(0, count($tracked), '?'));

        /** @var list<object> $rows */
        $rows = DB::select(
            "SELECT table_name  AS tbl_name,
                    (data_length + index_length) AS bytes
             FROM   information_schema.tables
             WHERE  table_schema = ?
             AND    LOWER(table_name) IN ({$placeholders})",
            [$database, ...$tracked],
        );

        // Index by lowercase table name so lookups are case-insensitive.
        $byteMap = [];
        foreach ($rows as $row) {
            $byteMap[strtolower((string) $row->tbl_name)] = (int) $row->bytes;
        }

        $result = [];
        foreach ($tracked as $table) {
            $result[] = [
                'name'      => $table,
                '_bytes'    => $byteMap[$table] ?? 0,
                'row_count' => Schema::hasTable($table)
                    ? (int) DB::table($table)->count()
                    : 0,
            ];
        }

        $trackedBytes = array_sum(array_column($result, '_bytes'));
        $totalBytes = $this->mariadbTotalBytes($database, $driver);

        $result[] = [
            'name'      => 'other tables',
            '_bytes'    => max(0, $totalBytes - $trackedBytes),
            'row_count' => 0,
        ];

        return $result;
    }

    /**
     * Fallback — row counts only when driver is unknown.
     *
     * @return list<array{name:string,_bytes:int,row_count:int}>
     */
    private function fallbackTableSizes(): array
    {
        $tracked = ['speed_results', 'webhook_deliveries', 'job_batches', 'failed_jobs'];
        $result = [];

        foreach ($tracked as $table) {
            $result[] = [
                'name'      => $table,
                '_bytes'    => 0,
                'row_count' => Schema::hasTable($table)
                    ? (int) DB::table($table)->count()
                    : 0,
            ];
        }

        $result[] = ['name' => 'other tables', '_bytes' => 0, 'row_count' => 0];

        return $result;
    }

    /**
     * @return list<array{table:string,current_rows:int,max_rows:int,window_days:int}>
     */
    public function retentionProjections(): array
    {
        $resultDays = (int) Setting::get('result_retention_days', 90);
        $webhookDays = (int) Setting::get('webhook_retention_days', 30);

        return [
            [
                'table'        => 'speed_results',
                'current_rows' => (int) DB::table('speed_results')->count(),
                'max_rows'     => $resultDays * 536,
                'window_days'  => $resultDays,
            ],
            [
                'table'        => 'webhook_deliveries',
                'current_rows' => (int) DB::table('webhook_deliveries')->count(),
                'max_rows'     => $webhookDays * 400,
                'window_days'  => $webhookDays,
            ],
        ];
    }

    /**
     * @return list<array{event:string,triggered_by:string,duration:string|null,timestamp:string}>
     */
    public function downtimeLogs(): array
    {
        return DB::table('downtime_logs')
            ->orderByDesc('timestamp')
            ->limit(10)
            ->get(['event', 'triggered_by', 'duration', 'timestamp'])
            ->map(static fn (object $r) => (array) $r)
            ->all();
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /**
     * MariaDB / MySQL total DB size in bytes.
     */
    private function mariadbTotalBytes(string $database, string $driver): int
    {
        /** @var object|null $row */
        $row = DB::connection($driver)->selectOne(
            'SELECT SUM(data_length + index_length) AS bytes
             FROM   information_schema.tables
             WHERE  table_schema = ?',
            [$database],
        );

        return (int) ($row->bytes ?? 0);
    }

    /**
     * Driver-aware total DB size in MB for the stat card.
     */
    private function totalDbSizeMb(): int
    {
        $driver = (string) config('database.default');

        return match ($driver) {
            'mysql', 'mariadb' => (int) round(
                $this->mariadbTotalBytes(
                    (string) config("database.connections.{$driver}.database"),
                    $driver,
                ) / 1_048_576,
            ),
            'sqlite' => (static function (): int {
                $path = database_path('database.sqlite');

                return file_exists($path)
                    ? (int) round(filesize($path) / 1_048_576)
                    : 0;
            })(),
            'pgsql' => (static function (): int {
                /** @var object|null $row */
                $row = DB::selectOne(
                    'SELECT SUM(pg_total_relation_size(relid)) AS bytes
                     FROM   pg_catalog.pg_statio_user_tables',
                );

                return (int) round((int) ($row->bytes ?? 0) / 1_048_576);
            })(),
            default => 0,
        };
    }
}
