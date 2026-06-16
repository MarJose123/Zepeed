<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ping_alert_actions', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ping_alert_rule_id')
                ->constrained('ping_alert_rules')
                ->cascadeOnDelete();
            $table->string('type'); // email|webhook
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
        Schema::dropIfExists('ping_alert_actions');
    }
};
