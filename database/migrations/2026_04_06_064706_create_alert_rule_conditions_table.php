<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the speedtest "workflow rule conditions" table.
 *
 * See 2026_04_06_064657_create_alert_rules_table.php for why this file keeps
 * its legacy name while creating the `workflow_rule_conditions` table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_rule_conditions', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_rule_id')->constrained('workflow_rules')->cascadeOnDelete();
            $table->string('metric');   // status|download_mbps|upload_mbps|ping_ms|jitter_ms|packet_loss
            $table->string('operator'); // is|is_not|is_above|is_below
            $table->string('value');    // failed|success|skipped|numeric string
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_rule_conditions');
        Schema::dropIfExists('alert_rule_conditions');
    }
};
