<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speed_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider_slug');
            $table->string('status')
                ->comment("possible values: 'success' | 'failed' | 'skipped'");
            // ── Core metrics ──────────────────────────────────────────────
            $table->decimal('download_mbps', 8, 2)->nullable();
            $table->decimal('upload_mbps', 8, 2)->nullable();
            $table->decimal('ping_ms', 8, 2)->nullable();
            // ── Extended metrics ──────────────────────────────────────────
            $table->decimal('jitter_ms', 8, 2)->nullable();
            $table->decimal('packet_loss', 5, 2)->nullable();
            $table->unsignedBigInteger('download_bytes')->nullable();
            $table->unsignedBigInteger('upload_bytes')->nullable();
            // ── Connection metadata ───────────────────────────────────────
            $table->string('server_name')->nullable();
            $table->string('server_location')->nullable();
            $table->string('isp')->nullable();
            $table->string('client_ip')->nullable();
            // ── Failure detail ────────────────────────────────────────────
            $table->string('failure_reason')->nullable(); // SpeedtestFailureReason::value — only set when status = 'failed'
            $table->text('failure_message')->nullable(); // Human-readable error message from SpeedtestException
            // ── Raw output ────────────────────────────────────────────────
            $table->longText('raw_json')->nullable(); // verbatim CLI output — null when status != 'success'
            // ── Timing ────────────────────────────────────────────────────
            $table->timestamp('measured_at'); // when the test was actually run, not when the row was inserted
            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────────
            $table->index('provider_slug');
            $table->index('status');
            $table->index('measured_at');
            $table->index(['provider_slug', 'measured_at']); // composite — the most common query pattern: results for a specific provider ordered by time (dashboard charts, Results page)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speed_results');
    }
};
