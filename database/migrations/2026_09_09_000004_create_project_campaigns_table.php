<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('content_type', ['story', 'post'])->default('story');
            $table->text('caption')->nullable();
            $table->string('target_time'); // HH:mm
            $table->integer('images_per_post')->default(1);
            $table->enum('repeat_type', ['continuous', 'once', 'until_date'])->default('continuous');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('exclude_days')->nullable();
            $table->boolean('is_continuous')->default(true);
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_campaigns');
    }
};
