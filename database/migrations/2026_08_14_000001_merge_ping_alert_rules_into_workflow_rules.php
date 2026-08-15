<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Merge the "ping alert rules" feature into "workflow rules".
 *
 * Ping alert rules become ordinary `workflow_rules` rows with
 * `event = 'ping'` and a `ping_target_id`:
 *
 *   - `workflow_rules`            gains a nullable `ping_target_id` FK
 *     (cascade on delete) plus a unique (ping_target_id, name) index that
 *     mirrors the old `ping_alert_rules` constraint.
 *   - `workflow_rule_conditions`  gains a nullable `lookback_minutes`
 *     column (only ping conditions use it; speedtest conditions leave it
 *     NULL).
 *
 * Existing `ping_alert_*` rows are copied into the `workflow_*` tables
 * preserving their UUIDs (so conditions/actions FK references carry over)
 * and the legacy tables are dropped. Stored `ping-alerts:*` token abilities
 * are rewritten to `workflow-rules:*` so existing API/MCP tokens keep
 * working after a plain `php artisan migrate`.
 *
 * Every schema operation is guarded with Schema::hasTable()/hasColumn()
 * checks so the migration is a safe no-op for installs that already ran it
 * or never created the ping tables.
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const array ABILITY_MAP = [
        'ping-alerts:view'   => 'workflow-rules:view',
        'ping-alerts:create' => 'workflow-rules:create',
        'ping-alerts:update' => 'workflow-rules:update',
        'ping-alerts:delete' => 'workflow-rules:delete',
    ];

    public function up(): void
    {
        $hasPing = Schema::hasTable('ping_alert_rules');

        if (Schema::hasTable('workflow_rules') && ! Schema::hasColumn('workflow_rules', 'ping_target_id')) {
            Schema::table('workflow_rules', static function (Blueprint $table) {
                $table->foreignUuid('ping_target_id')->nullable()->constrained('ping_targets')->cascadeOnDelete();
                $table->unique(['ping_target_id', 'name']);
            });
        }

        if (Schema::hasTable('workflow_rule_conditions') && ! Schema::hasColumn('workflow_rule_conditions', 'lookback_minutes')) {
            Schema::table('workflow_rule_conditions', static function (Blueprint $table) {
                $table->unsignedSmallInteger('lookback_minutes')->nullable();
            });
        }

        if ($hasPing) {
            $this->copyPingRulesIntoWorkflowRules();

            Schema::dropIfExists('ping_alert_actions');
            Schema::dropIfExists('ping_alert_conditions');
            Schema::dropIfExists('ping_alert_rules');
        }

        $this->migrateTokenAbilities(self::ABILITY_MAP);
    }

    public function down(): void
    {
        $this->migrateTokenAbilities(array_flip(self::ABILITY_MAP));

        if (! Schema::hasTable('ping_alert_rules') && Schema::hasTable('workflow_rules') && Schema::hasColumn('workflow_rules', 'ping_target_id')) {
            $this->copyWorkflowRulesBackToPingTables();

            Schema::table('workflow_rules', static function (Blueprint $table) {
                $table->dropUnique(['ping_target_id', 'name']);
                $table->dropConstrainedForeignId('ping_target_id');
            });
        }

        if (Schema::hasTable('workflow_rule_conditions') && Schema::hasColumn('workflow_rule_conditions', 'lookback_minutes')) {
            Schema::table('workflow_rule_conditions', static function (Blueprint $table) {
                $table->dropColumn('lookback_minutes');
            });
        }
    }

    /**
     * Copy ping alert rows into the workflow tables, preserving UUIDs so the
     * conditions/actions foreign keys keep pointing at the same rules.
     */
    private function copyPingRulesIntoWorkflowRules(): void
    {
        DB::table('ping_alert_rules')
            ->orderBy('created_at')
            ->each(static function (object $rule): void {
                DB::table('workflow_rules')->insert([
                    'id'                 => $rule->id,
                    'name'               => $rule->name,
                    'provider_slug'      => null,
                    'ping_target_id'     => $rule->ping_target_id,
                    'event'              => 'ping',
                    'condition_operator' => $rule->condition_operator,
                    'is_active'          => $rule->is_active,
                    'cooldown_minutes'   => $rule->cooldown_minutes,
                    'last_triggered_at'  => $rule->last_triggered_at,
                    'created_at'         => $rule->created_at,
                    'updated_at'         => $rule->updated_at,
                ]);
            });

        DB::table('ping_alert_conditions')
            ->orderBy('created_at')
            ->each(static function (object $condition): void {
                DB::table('workflow_rule_conditions')->insert([
                    'id'               => $condition->id,
                    'workflow_rule_id' => $condition->ping_alert_rule_id,
                    'metric'           => $condition->metric,
                    'operator'         => $condition->operator,
                    'value'            => $condition->value,
                    'lookback_minutes' => $condition->lookback_minutes,
                    'sort_order'       => $condition->sort_order,
                    'created_at'       => $condition->created_at,
                    'updated_at'       => $condition->updated_at,
                ]);
            });

        DB::table('ping_alert_actions')
            ->orderBy('created_at')
            ->each(static function (object $action): void {
                DB::table('workflow_rule_actions')->insert([
                    'id'                => $action->id,
                    'workflow_rule_id'  => $action->ping_alert_rule_id,
                    'type'              => $action->type,
                    'mail_provider_id'  => $action->mail_provider_id ?? null,
                    'email_template_id' => $action->email_template_id ?? null,
                    'recipient_email'   => $action->recipient_email ?? null,
                    'webhook_id'        => $action->webhook_id ?? null,
                    'apprise_id'        => $action->apprise_id ?? null,
                    'sort_order'        => $action->sort_order,
                    'created_at'        => $action->created_at,
                    'updated_at'        => $action->updated_at,
                ]);
            });
    }

    /**
     * Reverse of copyPingRulesIntoWorkflowRules(): recreate the ping tables
     * and move workflow rules with event = 'ping' back into them.
     */
    private function copyWorkflowRulesBackToPingTables(): void
    {
        Schema::create('ping_alert_rules', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('ping_target_id')->constrained()->cascadeOnDelete();
            $table->string('condition_operator')->default('and');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('cooldown_minutes')->default(30);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            $table->unique(['ping_target_id', 'name']);
        });

        Schema::create('ping_alert_conditions', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ping_alert_rule_id')->constrained('ping_alert_rules')->cascadeOnDelete();
            $table->string('metric');
            $table->string('operator');
            $table->string('value');
            $table->unsignedSmallInteger('lookback_minutes')->default(5);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ping_alert_actions', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ping_alert_rule_id')->constrained('ping_alert_rules')->cascadeOnDelete();
            $table->string('type');
            $table->foreignUuid('mail_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('email_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_email')->nullable();
            $table->foreignUuid('webhook_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('apprise_id')->nullable()->constrained('apprises')->nullOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $pingRuleIds = DB::table('workflow_rules')
            ->where('event', 'ping')
            ->whereNotNull('ping_target_id')
            ->pluck('id');

        if ($pingRuleIds->isEmpty()) {
            return;
        }

        DB::table('workflow_rules')
            ->whereIn('id', $pingRuleIds)
            ->orderBy('created_at')
            ->each(static function (object $rule): void {
                DB::table('ping_alert_rules')->insert([
                    'id'                 => $rule->id,
                    'name'               => $rule->name,
                    'ping_target_id'     => $rule->ping_target_id,
                    'condition_operator' => $rule->condition_operator,
                    'is_active'          => $rule->is_active,
                    'cooldown_minutes'   => $rule->cooldown_minutes,
                    'last_triggered_at'  => $rule->last_triggered_at,
                    'created_at'         => $rule->created_at,
                    'updated_at'         => $rule->updated_at,
                ]);
            });

        DB::table('workflow_rule_conditions')
            ->whereIn('workflow_rule_id', $pingRuleIds)
            ->orderBy('created_at')
            ->each(static function (object $condition): void {
                DB::table('ping_alert_conditions')->insert([
                    'id'                 => $condition->id,
                    'ping_alert_rule_id' => $condition->workflow_rule_id,
                    'metric'             => $condition->metric,
                    'operator'           => $condition->operator,
                    'value'              => $condition->value,
                    'lookback_minutes'   => $condition->lookback_minutes ?? 5,
                    'sort_order'         => $condition->sort_order,
                    'created_at'         => $condition->created_at,
                    'updated_at'         => $condition->updated_at,
                ]);
            });

        DB::table('workflow_rule_actions')
            ->whereIn('workflow_rule_id', $pingRuleIds)
            ->orderBy('created_at')
            ->each(static function (object $action): void {
                DB::table('ping_alert_actions')->insert([
                    'id'                 => $action->id,
                    'ping_alert_rule_id' => $action->workflow_rule_id,
                    'type'               => $action->type,
                    'mail_provider_id'   => $action->mail_provider_id ?? null,
                    'email_template_id'  => $action->email_template_id ?? null,
                    'recipient_email'    => $action->recipient_email ?? null,
                    'webhook_id'         => $action->webhook_id ?? null,
                    'apprise_id'         => $action->apprise_id ?? null,
                    'sort_order'         => $action->sort_order,
                    'created_at'         => $action->created_at,
                    'updated_at'         => $action->updated_at,
                ]);
            });

        // Delete the migrated rows so the schema change is idempotent on down().
        DB::table('workflow_rule_actions')->whereIn('workflow_rule_id', $pingRuleIds)->delete();
        DB::table('workflow_rule_conditions')->whereIn('workflow_rule_id', $pingRuleIds)->delete();
        DB::table('workflow_rules')->whereIn('id', $pingRuleIds)->delete();
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
