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
        Schema::create('apprises', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('url');
            $table->json('tags')->nullable();        // tags used to target Apprise notification services
            $table->string('username')->nullable();  // HTTP Basic Auth username
            $table->text('password')->nullable();    // HTTP Basic Auth password (encrypted)
            $table->unsignedInteger('timeout')->default(30);
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
        Schema::dropIfExists('apprises');
    }
};
