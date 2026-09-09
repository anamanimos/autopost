<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sso_id')->nullable()->after('phone')->index();
            $table->string('sso_provider', 50)->nullable()->after('sso_id');
            $table->json('sso_data')->nullable()->after('sso_provider');
            $table->timestamp('approved_at')->nullable()->after('sso_data');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['sso_id', 'sso_provider', 'sso_data', 'approved_at', 'approved_by']);
        });
    }
};
