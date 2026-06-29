<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prometheus', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('is_enabled')->default(false);
            $table->json('allowed_ips')->nullable()->comment('Array of IPs/CIDRs — empty means allow all');
            $table->unsignedSmallInteger('cache_ttl')->default(60)->comment('Seconds the rendered metrics output is cached');
            $table->boolean('include_speed')->default(true)->comment('Groups 1+2+3+10: speed gauges, 24h health, provider state, failure details');
            $table->boolean('include_ping')->default(true)->comment('Groups 4+5: ping gauges and 24h health');
            $table->boolean('include_system')->default(true)->comment('Groups 6+7+8+9: alert rules, maintenance, app info, webhooks');
            $table->json('providers')->nullable()->comment('Provider slugs included in speed metrics');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prometheus');
    }
};
