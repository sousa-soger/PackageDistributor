<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gitlab_id')->nullable()->after('id');
            $table->string('gitlab_username')->nullable()->after('gitlab_id');
            $table->string('gitlab_name')->nullable()->after('gitlab_username');
            $table->string('gitlab_email')->nullable()->after('gitlab_name');
            $table->string('gitlab_avatar')->nullable()->after('gitlab_email');

            $table->text('gitlab_token')->nullable()->after('password');
            $table->text('gitlab_refresh_token')->nullable()->after('gitlab_token');
            $table->timestamp('gitlab_token_expires_at')->nullable()->after('gitlab_refresh_token');
            $table->timestamp('gitlab_connected_at')->nullable()->after('gitlab_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gitlab_id',
                'gitlab_username',
                'gitlab_name',
                'gitlab_email',
                'gitlab_avatar',
                'gitlab_token',
                'gitlab_refresh_token',
                'gitlab_token_expires_at',
                'gitlab_connected_at',
            ]);
        });
    }
};
