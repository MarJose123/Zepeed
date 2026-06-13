<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ping_results', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ping_target_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // success|partial|failed
            $table->unsignedSmallInteger('packets_sent');
            $table->unsignedSmallInteger('packets_received');
            $table->decimal('packet_loss_percent', 5, 2);
            $table->decimal('min_ms', 8, 2)->nullable();
            $table->decimal('avg_ms', 8, 2)->nullable();
            $table->decimal('max_ms', 8, 2)->nullable();
            $table->decimal('stddev_ms', 8, 2)->nullable();
            $table->text('raw_output')->nullable();
            $table->string('failure_reason')->nullable(); // timeout|unreachable|dns_failed
            $table->timestamp('measured_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['ping_target_id', 'measured_at']);
            $table->index('status');
            $table->index('measured_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ping_results');
    }
};
