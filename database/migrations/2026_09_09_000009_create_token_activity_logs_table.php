<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // 'oauth_connect', 'manual_refresh', 'token_debug', 'full_sync', 'test_connection'
            $table->enum('status', ['success', 'failed']);
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_activity_logs');
    }
};
