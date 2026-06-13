<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ping_alert_conditions', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ping_alert_rule_id')
                ->constrained('ping_alert_rules')
                ->cascadeOnDelete();
            $table->string('metric');   // latency_avg|latency_max|packet_loss|consecutive_failures
            $table->string('operator'); // is_above|is_below|is_above_or_equal|is_below_or_equal|is|is_not
            $table->string('value');
            $table->unsignedSmallInteger('lookback_minutes')->default(5);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ping_alert_conditions');
    }
};
