<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rule_conditions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('alert_rule_id')->constrained()->cascadeOnDelete();
            $table->string('metric');   // status|download_mbps|upload_mbps|ping_ms|jitter_ms|packet_loss
            $table->string('operator'); // is|is_not|is_above|is_below
            $table->string('value');    // failed|success|skipped|numeric string
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rule_conditions');
    }
};
