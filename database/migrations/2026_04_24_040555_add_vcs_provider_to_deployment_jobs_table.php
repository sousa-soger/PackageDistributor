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
        Schema::table('deployment_jobs', function (Blueprint $table) {
            $table->string('vcs_provider', 10)->default('github')->after('repo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deployment_jobs', function (Blueprint $table) {
            $table->dropColumn('vcs_provider');
        });
    }
};
