<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename the legacy 1.x "alert rules" tables to "workflow rules".
 *
 * Fresh 2.x installs create the tables directly as `workflow_*` (see the
 * 2026_04_06_* create migrations), so every operation here is guarded with
 * `Schema::hasTable()` / `Schema::hasColumn()` checks and becomes a no-op for
 * them. It only acts on databases that still carry the legacy 1.x table names
 * (`alert_rules`, `alert_rule_conditions`, `alert_rule_actions`), renaming
 * tables and columns in place so existing rows are preserved.
 *
 * It also rewrites stored `alerts:*` token abilities to `workflow-rules:*`
 * (idempotent) so existing API/MCP tokens keep working after a plain
 * `php artisan migrate` — no user action required.
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const array ABILITY_MAP = [
        'alerts:view'   => 'workflow-rules:view',
        'alerts:create' => 'workflow-rules:create',
        'alerts:update' => 'workflow-rules:update',
        'alerts:delete' => 'workflow-rules:delete',
    ];

    public function up(): void
    {
        if (Schema::hasTable('alert_rules') && ! Schema::hasTable('workflow_rules')) {
            Schema::rename('alert_rules', 'workflow_rules');
        }

        if (Schema::hasTable('alert_rule_conditions') && ! Schema::hasTable('workflow_rule_conditions')) {
            Schema::rename('alert_rule_conditions', 'workflow_rule_conditions');
        }

        if (Schema::hasTable('alert_rule_actions') && ! Schema::hasTable('workflow_rule_actions')) {
            Schema::rename('alert_rule_actions', 'workflow_rule_actions');
        }

        if (Schema::hasTable('workflow_rule_conditions') && Schema::hasColumn('workflow_rule_conditions', 'alert_rule_id')) {
            Schema::table('workflow_rule_conditions', static function (Blueprint $table) {
                $table->renameColumn('alert_rule_id', 'workflow_rule_id');
            });
        }

        if (Schema::hasTable('workflow_rule_actions') && Schema::hasColumn('workflow_rule_actions', 'alert_rule_id')) {
            Schema::table('workflow_rule_actions', static function (Blueprint $table) {
                $table->renameColumn('alert_rule_id', 'workflow_rule_id');
            });
        }

        $this->migrateTokenAbilities(self::ABILITY_MAP);
    }

    public function down(): void
    {
        $this->migrateTokenAbilities(array_flip(self::ABILITY_MAP));

        // Rename columns back while the tables are still named workflow_*.
        if (Schema::hasTable('workflow_rule_conditions')
            && Schema::hasColumn('workflow_rule_conditions', 'workflow_rule_id')
            && ! Schema::hasTable('alert_rule_conditions')
        ) {
            Schema::table('workflow_rule_conditions', static function (Blueprint $table) {
                $table->renameColumn('workflow_rule_id', 'alert_rule_id');
            });
        }

        if (Schema::hasTable('workflow_rule_actions')
            && Schema::hasColumn('workflow_rule_actions', 'workflow_rule_id')
            && ! Schema::hasTable('alert_rule_actions')
        ) {
            Schema::table('workflow_rule_actions', static function (Blueprint $table) {
                $table->renameColumn('workflow_rule_id', 'alert_rule_id');
            });
        }

        if (Schema::hasTable('workflow_rules') && ! Schema::hasTable('alert_rules')) {
            Schema::rename('workflow_rules', 'alert_rules');
        }

        if (Schema::hasTable('workflow_rule_conditions') && ! Schema::hasTable('alert_rule_conditions')) {
            Schema::rename('workflow_rule_conditions', 'alert_rule_conditions');
        }

        if (Schema::hasTable('workflow_rule_actions') && ! Schema::hasTable('alert_rule_actions')) {
            Schema::rename('workflow_rule_actions', 'alert_rule_actions');
        }
    }

    /**
     * Rewrite stored token abilities using the given legacy -> current map.
     * Idempotent: values already renamed (or unrelated) are left untouched.
     *
     * @param array<string, string> $map
     */
    private function migrateTokenAbilities(array $map): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            return;
        }

        $tokens = DB::table('personal_access_tokens')
            ->whereNotNull('abilities')
            ->get(['id', 'abilities']);

        foreach ($tokens as $token) {
            $abilities = json_decode((string) $token->abilities, true);

            if (! is_array($abilities)) {
                continue;
            }

            $updated = array_values(array_unique(array_map(
                static fn (mixed $ability): string => $map[(string) $ability] ?? (string) $ability,
                $abilities,
            )));

            if ($updated !== $abilities) {
                DB::table('personal_access_tokens')
                    ->where('id', $token->id)
                    ->update(['abilities' => json_encode($updated)]);
            }
        }
    }
};
