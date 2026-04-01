<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mail_providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('driver');
            $table->string('label');
            $table->integer('priority');
            $table->boolean('is_active')->default(true);
            $table->longText('config');
            $table->string('from_address');
            $table->string('from_name');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_providers');
    }
};
