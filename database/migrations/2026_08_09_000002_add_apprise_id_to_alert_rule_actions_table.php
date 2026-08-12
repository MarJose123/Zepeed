<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `apprise_id` to the speedtest workflow rule actions table.
 *
 * The file keeps its legacy name (see 2026_04_06_064657_create_alert_rules_table.php):
 * on fresh 2.x installs it adds the column to `workflow_rule_actions`, while
 * 1.x installs already applied it to `alert_rule_actions` (carried over by the
 * 2026_08_12 rename migration).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Guard symmetrically with down(): a 1.x database that predates the
        // Apprise feature has this migration pending and still carries the
        // legacy alert_rule_actions name (the rename migration runs later).
        $table = Schema::hasTable('workflow_rule_actions')
            ? 'workflow_rule_actions'
            : 'alert_rule_actions';

        Schema::table($table, static function (Blueprint $table) {
            $table->foreignUuid('apprise_id')
                ->nullable()
                ->after('webhook_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Defensive: during a 1.x -> 2.x upgrade rollback the table is still
        // named alert_rule_actions (the rename migration's down() ran first).
        $table = Schema::hasTable('workflow_rule_actions')
            ? 'workflow_rule_actions'
            : 'alert_rule_actions';

        Schema::table($table, static function (Blueprint $table) {
            $table->dropConstrainedForeignId('apprise_id');
        });
    }
};
