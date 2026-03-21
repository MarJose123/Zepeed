<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider_slug')->unique(); // one schedule row per provider — unique constraint enforced

            $table->string('cron_expression')->nullable(); // e.g. "*/30 * * * *" — null means disabled / not configured yet

            $table->boolean('is_enabled')->default(false); // independent of the provider's is_enabled provider can be enabled but schedule paused

            $table->timestamp('last_scheduled_at')->nullable(); // updated every time the scheduler fires this provider

            $table->timestamps();

            $table->index('provider_slug');
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_schedules');
    }
};
