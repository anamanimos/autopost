<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Kredensial Pengembang TikTok API
        if (!Schema::hasTable('tiktok_credentials')) {
            Schema::create('tiktok_credentials', function (Blueprint $table) {
                $table->id();
                $table->string('client_key')->nullable();
                $table->text('client_secret')->nullable();
                $table->string('status', 30)->default('unconfigured'); // unconfigured, valid, expired
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Tambah kolom TikTok pada tabel connected_accounts
        Schema::table('connected_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('connected_accounts', 'page_id')) {
                $table->string('page_id')->nullable()->change();
            }

            if (!Schema::hasColumn('connected_accounts', 'tiktok_open_id')) {
                $table->string('tiktok_open_id')->nullable()->after('threads_publishing_quota_total')->index();
                $table->string('tiktok_username')->nullable()->after('tiktok_open_id');
                $table->string('tiktok_display_name')->nullable()->after('tiktok_username');
                $table->text('tiktok_avatar_url')->nullable()->after('tiktok_display_name');
                $table->text('tiktok_access_token')->nullable()->after('tiktok_avatar_url');
                $table->text('tiktok_refresh_token')->nullable()->after('tiktok_access_token');
                $table->timestamp('tiktok_token_expires_at')->nullable()->after('tiktok_refresh_token');
                $table->timestamp('tiktok_refresh_token_expires_at')->nullable()->after('tiktok_token_expires_at');
                $table->integer('tiktok_publishing_quota_usage')->default(0)->after('tiktok_refresh_token_expires_at');
                $table->integer('tiktok_publishing_quota_total')->default(100)->after('tiktok_publishing_quota_usage');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiktok_credentials');

        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'tiktok_open_id',
                'tiktok_username',
                'tiktok_display_name',
                'tiktok_avatar_url',
                'tiktok_access_token',
                'tiktok_refresh_token',
                'tiktok_token_expires_at',
                'tiktok_refresh_token_expires_at',
                'tiktok_publishing_quota_usage',
                'tiktok_publishing_quota_total',
            ]);
        });
    }
};
