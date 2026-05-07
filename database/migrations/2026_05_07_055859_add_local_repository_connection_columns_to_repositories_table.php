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
        Schema::table('repositories', function (Blueprint $table) {
            $table->string('type')->nullable()->after('provider');
            $table->string('remote_ip')->nullable()->after('server_protocol');
            $table->string('remote_path', 500)->nullable()->after('remote_ip');
            $table->string('storage_path')->nullable()->after('remote_path');
            $table->boolean('has_git_history')->default(true)->after('storage_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'remote_ip',
                'remote_path',
                'storage_path',
                'has_git_history',
            ]);
        });
    }
};
