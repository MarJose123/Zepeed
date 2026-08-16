<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the guarded legacy rename migration
 * (2026_08_12_000001_rename_legacy_alert_rules_to_workflow_rules_table).
 *
 * Fresh 2.x installs create the `workflow_*` tables directly, so the migration
 * must be a no-op for them; 1.x databases carry the legacy `alert_*` names and
 * must be renamed in place with rows preserved.
 */
class WorkflowRulesRenameMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function renameMigration(): object
    {
        return require database_path('migrations/2026_08_12_000001_rename_legacy_alert_rules_to_workflow_rules_table.php');
    }

    /**
     * Rolling back past the rename migration on a fresh install must not throw,
     * and the create migrations' defensive down() (dropping both table names)
     * must clean up completely regardless of the rename migration's down().
     */
    public function testFullRollbackOnFreshInstallLeavesNoTables(): void
    {
        $this->assertTrue(Schema::hasTable('workflow_rules'));

        // down() fires on the live workflow_* schema (fresh install) — safe.
        $this->renameMigration()->down();

        $createRules = require database_path('migrations/2026_04_06_064657_create_alert_rules_table.php');
        $createConditions = require database_path('migrations/2026_04_06_064706_create_alert_rule_conditions_table.php');
        $createActions = require database_path('migrations/2026_04_06_064713_create_alert_rule_actions_table.php');

        // Children first, then the parent, so FK references never dangle.
        $createActions->down();
        $createConditions->down();
        $createRules->down();
        foreach (['workflow_rules', 'workflow_rule_conditions', 'workflow_rule_actions', 'alert_rules', 'alert_rule_conditions', 'alert_rule_actions'] as $table) {
            $this->assertFalse(Schema::hasTable($table), "Table [{$table}] should have been dropped.");
        }
    }

    /**
     * Fresh installs (tables already named workflow_*) — up() must not error
     * and must leave the schema untouched.
     */
    public function testUpIsNoOpWhenLegacyTablesAreAbsent(): void
    {
        $this->assertTrue(Schema::hasTable('workflow_rules'));
        $this->assertTrue(Schema::hasTable('workflow_rule_conditions'));
        $this->assertTrue(Schema::hasTable('workflow_rule_actions'));
        $this->assertFalse(Schema::hasTable('alert_rules'));

        $this->renameMigration()->up(); // must not throw

        $this->assertTrue(Schema::hasTable('workflow_rules'));
        $this->assertFalse(Schema::hasTable('alert_rules'));
    }

    /**
     * A 1.x database (legacy alert_* tables) is renamed in place, preserving
     * rows and rewriting legacy token abilities.
     */
    public function testUpRenamesLegacyTablesPreservingRows(): void
    {
        // Simulate a 1.x database: drop the fresh workflow_* tables and
        // recreate the legacy alert_* schema with data.
        Schema::dropIfExists('workflow_rule_actions');
        Schema::dropIfExists('workflow_rule_conditions');
        Schema::dropIfExists('workflow_rules');

        $this->createLegacySchema();

        $ruleId = (string) Str::uuid();
        $conditionId = (string) Str::uuid();
        $actionId = (string) Str::uuid();

        DB::table('alert_rules')->insert([
            'id'                 => $ruleId,
            'name'               => 'Legacy rule',
            'event'              => 'run_fails',
            'condition_operator' => 'and',
            'is_active'          => true,
            'cooldown_minutes'   => 30,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        DB::table('alert_rule_conditions')->insert([
            'id'            => $conditionId,
            'alert_rule_id' => $ruleId,
            'metric'        => 'status',
            'operator'      => 'is',
            'value'         => 'failed',
            'sort_order'    => 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        DB::table('alert_rule_actions')->insert([
            'id'              => $actionId,
            'alert_rule_id'   => $ruleId,
            'type'            => 'email',
            'recipient_email' => 'ops@example.com',
            'sort_order'      => 0,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        DB::table('personal_access_tokens')->insert([
            'id'               => 1,
            'tokenable_type'   => User::class,
            'tokenable_id'     => 1,
            'name'             => 'legacy',
            'token'            => Str::random(40),
            'abilities'        => json_encode(['alerts:view', 'webhooks:view']),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->renameMigration()->up();

        // Tables renamed, legacy names gone.
        $this->assertTrue(Schema::hasTable('workflow_rules'));
        $this->assertFalse(Schema::hasTable('alert_rules'));

        // Rows preserved.
        $this->assertDatabaseHas('workflow_rules', ['name' => 'Legacy rule']);
        $this->assertDatabaseHas('workflow_rule_conditions', [
            'workflow_rule_id' => $ruleId,
            'metric'           => 'status',
        ]);
        $this->assertDatabaseHas('workflow_rule_actions', [
            'workflow_rule_id' => $ruleId,
            'recipient_email'  => 'ops@example.com',
        ]);

        // Column renamed.
        $this->assertTrue(Schema::hasColumn('workflow_rule_conditions', 'workflow_rule_id'));
        $this->assertFalse(Schema::hasColumn('workflow_rule_conditions', 'alert_rule_id'));

        // Token abilities rewritten.
        $this->assertEqualsCanonicalizing(
            ['workflow-rules:view', 'webhooks:view'],
            json_decode((string) DB::table('personal_access_tokens')->where('id', 1)->value('abilities'), true),
        );
    }

    /**
     * down() restores the legacy table and column names.
     */
    public function testDownRestoresLegacyNames(): void
    {
        Schema::dropIfExists('workflow_rule_actions');
        Schema::dropIfExists('workflow_rule_conditions');
        Schema::dropIfExists('workflow_rules');

        $this->createLegacySchema();
        $this->renameMigration()->up();

        $this->renameMigration()->down();

        $this->assertTrue(Schema::hasTable('alert_rules'));
        $this->assertTrue(Schema::hasTable('alert_rule_conditions'));
        $this->assertTrue(Schema::hasTable('alert_rule_actions'));
        $this->assertFalse(Schema::hasTable('workflow_rules'));

        $this->assertTrue(Schema::hasColumn('alert_rule_conditions', 'alert_rule_id'));
        $this->assertFalse(Schema::hasColumn('alert_rule_conditions', 'workflow_rule_id'));
    }

    private function createLegacySchema(): void
    {
        Schema::create('alert_rules', static function ($table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('provider_slug')->nullable();
            $table->string('event');
            $table->string('condition_operator')->default('and');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('cooldown_minutes')->default(30);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('alert_rule_conditions', static function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('alert_rule_id');
            $table->string('metric');
            $table->string('operator');
            $table->string('value');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('alert_rule_actions', static function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('alert_rule_id');
            $table->string('type');
            $table->string('recipient_email')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
}
