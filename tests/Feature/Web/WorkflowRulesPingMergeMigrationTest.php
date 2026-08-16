<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the ping alert -> workflow rules merge migration
 * (2026_08_14_000001_merge_ping_alert_rules_into_workflow_rules).
 *
 * On fresh installs the migration adds the ping columns to the workflow_*
 * tables and is a no-op for the (absent) ping tables; databases that still
 * carry the legacy `ping_alert_*` tables get their rows copied into
 * workflow_* (as event = 'ping' rules), the legacy tables dropped, and stored
 * `ping-alerts:*` token abilities rewritten to `workflow-rules:*`.
 */
class WorkflowRulesPingMergeMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function mergeMigration(): object
    {
        return require database_path('migrations/2026_08_14_000001_merge_ping_alert_rules_into_workflow_rules.php');
    }

    /**
     * Fresh installs: the merge migration adds ping_target_id and
     * lookback_minutes, and drops the (empty) ping tables. Re-running up()
     * must be a safe no-op.
     */
    public function testFreshInstallHasPingColumnsAndNoPingTables(): void
    {
        $this->assertTrue(Schema::hasColumn('workflow_rules', 'ping_target_id'));
        $this->assertTrue(Schema::hasColumn('workflow_rule_conditions', 'lookback_minutes'));
        $this->assertFalse(Schema::hasTable('ping_alert_rules'));

        $this->mergeMigration()->up(); // must not throw

        $this->assertTrue(Schema::hasColumn('workflow_rules', 'ping_target_id'));
        $this->assertFalse(Schema::hasTable('ping_alert_rules'));
    }

    /**
     * Databases with legacy ping_alert_* tables: rows are copied into the
     * workflow_* tables (event = 'ping', UUIDs preserved), legacy tables are
     * dropped, and ping-alerts:* abilities are rewritten to workflow-rules:*.
     */
    public function testUpMergesLegacyPingRowsIntoWorkflowRules(): void
    {
        $this->createLegacyPingSchema();

        $targetId = (string) Str::uuid();
        DB::table('ping_targets')->insert([
            'id'         => $targetId,
            'label'      => 'Primary DNS',
            'host'       => '1.1.1.1',
            'is_enabled' => true,
            'packets'    => 4,
            'status'     => 'ok',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ruleId = (string) Str::uuid();
        $conditionId = (string) Str::uuid();
        $actionId = (string) Str::uuid();

        DB::table('ping_alert_rules')->insert([
            'id'                 => $ruleId,
            'name'               => 'Legacy ping rule',
            'ping_target_id'     => $targetId,
            'condition_operator' => 'and',
            'is_active'          => true,
            'cooldown_minutes'   => 30,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        DB::table('ping_alert_conditions')->insert([
            'id'                 => $conditionId,
            'ping_alert_rule_id' => $ruleId,
            'metric'             => 'latency_avg',
            'operator'           => 'is_above',
            'value'              => '100',
            'lookback_minutes'   => 5,
            'sort_order'         => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        DB::table('ping_alert_actions')->insert([
            'id'                 => $actionId,
            'ping_alert_rule_id' => $ruleId,
            'type'               => 'email',
            'recipient_email'    => 'ops@example.com',
            'sort_order'         => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        DB::table('personal_access_tokens')->insert([
            'id'               => 1,
            'tokenable_type'   => User::class,
            'tokenable_id'     => 1,
            'name'             => 'legacy-ping',
            'token'            => Str::random(40),
            'abilities'        => json_encode(['ping-alerts:view', 'webhooks:view']),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->mergeMigration()->up();

        $this->assertFalse(Schema::hasTable('ping_alert_rules'));
        $this->assertFalse(Schema::hasTable('ping_alert_conditions'));
        $this->assertFalse(Schema::hasTable('ping_alert_actions'));

        $this->assertDatabaseHas('workflow_rules', [
            'id'             => $ruleId,
            'name'           => 'Legacy ping rule',
            'event'          => 'ping',
            'ping_target_id' => $targetId,
        ]);
        $this->assertDatabaseHas('workflow_rule_conditions', [
            'workflow_rule_id' => $ruleId,
            'metric'           => 'latency_avg',
            'lookback_minutes' => 5,
        ]);
        $this->assertDatabaseHas('workflow_rule_actions', [
            'workflow_rule_id' => $ruleId,
            'recipient_email'  => 'ops@example.com',
        ]);

        $this->assertEqualsCanonicalizing(
            ['workflow-rules:view', 'webhooks:view'],
            json_decode((string) DB::table('personal_access_tokens')->where('id', 1)->value('abilities'), true),
        );
    }

    /**
     * down() recreates the ping_alert_* tables and moves ping workflow rules
     * back into them, then removes the ping columns.
     */
    public function testDownRestoresLegacyPingTables(): void
    {
        $this->createLegacyPingSchema();

        $targetId = (string) Str::uuid();
        DB::table('ping_targets')->insert([
            'id'         => $targetId,
            'label'      => 'Primary DNS',
            'host'       => '1.1.1.1',
            'is_enabled' => true,
            'packets'    => 4,
            'status'     => 'ok',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ping_alert_rules')->insert([
            'id'                 => (string) Str::uuid(),
            'name'               => 'Legacy ping rule',
            'ping_target_id'     => $targetId,
            'condition_operator' => 'and',
            'is_active'          => true,
            'cooldown_minutes'   => 30,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->mergeMigration()->up();
        $this->mergeMigration()->down();

        $this->assertTrue(Schema::hasTable('ping_alert_rules'));
        $this->assertDatabaseHas('ping_alert_rules', ['name' => 'Legacy ping rule']);

        $this->assertFalse(Schema::hasColumn('workflow_rules', 'ping_target_id'));
        $this->assertFalse(Schema::hasColumn('workflow_rule_conditions', 'lookback_minutes'));
    }

    private function createLegacyPingSchema(): void
    {
        Schema::create('ping_alert_rules', static function ($table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('ping_target_id');
            $table->string('condition_operator')->default('and');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('cooldown_minutes')->default(30);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            $table->unique(['ping_target_id', 'name']);
        });

        Schema::create('ping_alert_conditions', static function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('ping_alert_rule_id');
            $table->string('metric');
            $table->string('operator');
            $table->string('value');
            $table->unsignedSmallInteger('lookback_minutes')->default(5);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ping_alert_actions', static function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('ping_alert_rule_id');
            $table->string('type');
            $table->string('recipient_email')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
}
