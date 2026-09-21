<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_campaign_id')->nullable()->constrained('project_campaigns')->nullOnDelete();
            $table->string('item_code')->unique();
            $table->foreignId('media_file_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->string('media_path')->nullable();
            $table->json('media_paths')->nullable();
            $table->date('target_date');
            $table->string('target_time');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'partially_failed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['target_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
