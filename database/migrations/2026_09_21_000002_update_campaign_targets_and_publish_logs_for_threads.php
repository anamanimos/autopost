<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_targets', function (Blueprint $table) {
            $table->string('platform_target', 50)->default('all')->change();
        });

        Schema::table('publish_logs', function (Blueprint $table) {
            $table->string('platform', 30)->change();
        });
    }

    public function down(): void
    {
        Schema::table('campaign_targets', function (Blueprint $table) {
            $table->string('platform_target', 50)->default('both')->change();
        });

        Schema::table('publish_logs', function (Blueprint $table) {
            $table->string('platform', 30)->change();
        });
    }
};
