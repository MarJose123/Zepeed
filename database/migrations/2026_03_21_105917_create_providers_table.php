<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')
                ->comment("values: 'speedtest' | 'librespeed' | 'fastcom'")
                ->unique();
            $table->string('name');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('alert_on_failure')->default(true);
            $table->text('server_url')
                ->comment('LibreSpeed only — self-hosted instance URL')
                ->nullable();
            $table->text('server_id')
                ->comment('Speedtest (Ookla) only — numeric server ID string')
                ->nullable();
            $table->text('extra_flags')->nullable();
            $table->json('meta')
                ->comment('Reserved for future driver-specific config, not yet used')
                ->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status')
                ->comment("Denormalized from speed_results for dashboard performance. Values: 'success' | 'failed' | 'skipped' | null (never run)")
                ->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
