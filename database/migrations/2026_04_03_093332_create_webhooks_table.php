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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('url');
            $table->string('method')->default('POST');
            $table->text('secret')->nullable();        // encrypted
            $table->json('headers')->nullable();       // custom headers
            $table->unsignedInteger('timeout')->default(30);
            $table->unsignedInteger('retry_attempts')->default(3);
            $table->boolean('verify_ssl')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_fired_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
