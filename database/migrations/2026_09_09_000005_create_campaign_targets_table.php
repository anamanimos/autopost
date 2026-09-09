<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_campaign_id')->constrained('project_campaigns')->cascadeOnDelete();
            $table->foreignId('connected_account_id')->constrained('connected_accounts')->cascadeOnDelete();
            $table->enum('platform_target', ['both', 'instagram_only', 'facebook_only'])->default('both');
            $table->timestamps();

            $table->unique(['project_campaign_id', 'connected_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_targets');
    }
};
