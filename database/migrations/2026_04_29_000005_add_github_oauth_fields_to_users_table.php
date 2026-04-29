<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('github_id')->nullable()->after('gitlab_connected_at');
            $table->string('github_username')->nullable()->after('github_id');
            $table->string('github_name')->nullable()->after('github_username');
            $table->string('github_email')->nullable()->after('github_name');
            $table->string('github_avatar')->nullable()->after('github_email');

            $table->text('github_token')->nullable()->after('gitlab_token');
            $table->text('github_refresh_token')->nullable()->after('github_token');
            $table->timestamp('github_token_expires_at')->nullable()->after('github_refresh_token');
            $table->timestamp('github_connected_at')->nullable()->after('github_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'github_id',
                'github_username',
                'github_name',
                'github_email',
                'github_avatar',
                'github_token',
                'github_refresh_token',
                'github_token_expires_at',
                'github_connected_at',
            ]);
        });
    }
};
