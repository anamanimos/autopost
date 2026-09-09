<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('app_id')->nullable();
            $table->text('app_secret')->nullable();
            $table->string('graph_version')->default('v22.0');
            $table->text('user_access_token')->nullable();
            $table->text('system_user_token')->nullable();
            $table->string('token_type')->nullable(); // 'oauth_user' or 'system_user'
            $table->timestamp('token_expires_at')->nullable();
            $table->enum('token_status', ['unconfigured', 'valid', 'expired', 'needs_reauth'])->default('unconfigured');
            $table->string('webhook_verify_token')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_credentials');
    }
};
