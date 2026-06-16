<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ping_targets', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('label');
            $table->string('host');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('packets')->default(4);
            $table->unsignedSmallInteger('timeout_seconds')->default(5);
            $table->string('status')->default('pending'); // pending|ok|warn|failed
            $table->decimal('last_avg_ms', 8, 2)->nullable();
            $table->decimal('last_loss_percent', 5, 2)->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();

            $table->unique('label');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ping_targets');
    }
};
