<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_windows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('label'); // Human-readable name e.g. "ISP maintenance" "Weekly backup"
            $table->string('type'); // App\Enums\MaintenanceWindowType: 'indefinite'|'one_time'|'recurring'
            $table->boolean('is_active')->default(true); // Master switch — false = window exists but is disabled
            $table->json('providers')->default('["all"]');
            // ^ JSON array of provider slugs this window applies to.
            //   Special value ["all"] means every provider is suppressed.
            //   e.g. ["librespeed"] or ["speedtest","fastcom"]
            // ── One-time window fields ────────────────────────────────────
            $table->timestamp('starts_at')->nullable(); // Required when type = 'one_time'
            $table->timestamp('ends_at')->nullable(); // Required when type = 'one_time'
            // ── Recurring window fields ───────────────────────────────────
            $table->string('cron_expression')->nullable(); // Required when type = 'recurring' e.g. "0 2 * * 0"
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            // ^ Required when type = 'recurring' — how long the window lasts
            //   e.g. cron = "0 2 * * 0" + duration = 120 = every Sunday 02:00–04:00
            $table->text('notes')->nullable(); // Optional free-text for context e.g. "ISP ticket #12345"

            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
            $table->index(['is_active', 'type']); // Most common query: active windows of a specific type
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_windows');
    }
};
