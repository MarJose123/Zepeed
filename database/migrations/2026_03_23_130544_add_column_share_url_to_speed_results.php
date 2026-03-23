<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('speed_results', function (Blueprint $table) {
            $table->string('share_url')->nullable()->after('isp');
        });
    }

    public function down(): void
    {
        Schema::table('speed_results', function (Blueprint $table) {
            $table->dropColumn('share_url');
        });
    }
};
