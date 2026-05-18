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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('environment', 20);
            $table->string('host');
            $table->string('ssh_user', 100);
            $table->unsignedSmallInteger('port')->default(22);
            $table->string('deploy_path', 500);
            $table->string('health_check_url', 500)->nullable();
            $table->string('status', 40)->default('pending');
            $table->string('current_release', 120)->nullable();
            $table->boolean('auto_deploy_enabled')->default(false);
            $table->string('auto_deploy_strategy', 40)->default('on_package_ready');
            $table->boolean('production_approval_required')->default(true);
            $table->text('notes')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index(['user_id', 'environment', 'status']);
            $table->index(['project_id', 'environment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
