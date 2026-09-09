<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publish_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('project_campaign_id')->nullable()->constrained('project_campaigns')->nullOnDelete();
            $table->foreignId('connected_account_id')->nullable()->constrained('connected_accounts')->nullOnDelete();
            $table->enum('platform', ['instagram', 'facebook']);
            $table->enum('content_type', ['story', 'post']);
            $table->enum('action_status', ['success', 'failed', 'skipped']);
            $table->string('media_id')->nullable();
            $table->string('container_id')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('error_code')->nullable();
            $table->integer('error_subcode')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['schedule_id', 'platform']);
            $table->index(['action_status', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publish_logs');
    }
};
