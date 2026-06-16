<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ping_alert_rules', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('ping_target_id')->constrained()->cascadeOnDelete();
            $table->string('condition_operator')->default('and'); // and|or
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('cooldown_minutes')->default(30);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->unique(['ping_target_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ping_alert_rules');
    }
};
