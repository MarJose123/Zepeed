<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the speedtest "workflow rule actions" table.
 *
 * See 2026_04_06_064657_create_alert_rules_table.php for why this file keeps
 * its legacy name while creating the `workflow_rule_actions` table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_rule_actions', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_rule_id')->constrained('workflow_rules')->cascadeOnDelete();
            $table->string('type');                       // email|webhook
            $table->foreignUuid('mail_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('email_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_email')->nullable();
            $table->foreignUuid('webhook_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_rule_actions');
        Schema::dropIfExists('alert_rule_actions');
    }
};
