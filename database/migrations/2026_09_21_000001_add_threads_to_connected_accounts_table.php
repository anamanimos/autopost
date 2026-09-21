<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->string('threads_user_id')->nullable()->after('ig_profile_picture_url')->index();
            $table->string('threads_username')->nullable()->after('threads_user_id');
            $table->text('threads_profile_picture_url')->nullable()->after('threads_username');
            $table->text('threads_access_token')->nullable()->after('threads_profile_picture_url');
            $table->timestamp('threads_token_expires_at')->nullable()->after('threads_access_token');
            $table->integer('threads_publishing_quota_usage')->default(0)->after('threads_token_expires_at');
            $table->integer('threads_publishing_quota_total')->default(250)->after('threads_publishing_quota_usage');
        });

        Schema::table('meta_credentials', function (Blueprint $table) {
            $table->string('threads_app_id')->nullable()->after('app_secret');
            $table->text('threads_app_secret')->nullable()->after('threads_app_id');
        });
    }

    public function down(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'threads_user_id',
                'threads_username',
                'threads_profile_picture_url',
                'threads_access_token',
                'threads_token_expires_at',
                'threads_publishing_quota_usage',
                'threads_publishing_quota_total',
            ]);
        });

        Schema::table('meta_credentials', function (Blueprint $table) {
            $table->dropColumn([
                'threads_app_id',
                'threads_app_secret',
            ]);
        });
    }
};
