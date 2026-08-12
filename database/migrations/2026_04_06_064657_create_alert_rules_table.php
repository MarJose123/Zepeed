<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the speedtest "workflow rules" table.
 *
 * The file name intentionally keeps the legacy "alert_rules" name: Laravel
 * tracks migrations by file name, so 1.x installs have this migration
 * recorded as already-run. Renaming the file would make it re-run during a
 * 1.x -> 2.x upgrade and collide with the rename migration. Fresh 2.x
 * installs therefore create `workflow_rules` directly; existing 1.x databases
 * are renamed in place by 2026_08_12_000001_rename_legacy_alert_rules_to_workflow_rules_table.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_rules', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('provider_slug')->nullable(); // null = any provider
            $table->string('event');                     // run_completes|run_fails|run_skipped|any
            $table->string('condition_operator')->default('and'); // and|or
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('cooldown_minutes')->default(30);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop both names so rollback is safe whether the table was created
        // directly (fresh 2.x install) or restored from a renamed workflow_*
        // table (1.x -> 2.x upgrade rollback).
        Schema::dropIfExists('workflow_rules');
        Schema::dropIfExists('alert_rules');
    }
};
