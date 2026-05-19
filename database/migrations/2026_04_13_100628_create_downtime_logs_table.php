<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downtime_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('event', ['DOWN', 'UP']);
            $table->string('triggered_by')->default('system');
            $table->string('duration')->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();

            $table->index('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downtime_logs');
    }
};
