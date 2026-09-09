<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connected_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('page_id')->unique();
            $table->string('page_name');
            $table->string('page_category')->nullable();
            $table->text('page_access_token')->nullable();
            $table->string('ig_user_id')->nullable()->index();
            $table->string('ig_username')->nullable();
            $table->string('ig_name')->nullable();
            $table->text('ig_profile_picture_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('ig_publishing_quota_usage')->default(0);
            $table->integer('ig_publishing_quota_total')->default(100);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connected_accounts');
    }
};
