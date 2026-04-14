<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Batches table (future multi-package grouping) ─────────────────────
        Schema::create('deployment_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->enum('status', ['queued', 'running', 'completed', 'failed', 'cancelled'])
                  ->default('queued');
            $table->unsignedInteger('total_jobs')->default(0);
            $table->unsignedInteger('completed_jobs')->default(0);
            $table->unsignedInteger('failed_jobs')->default(0);
            $table->timestamps();
        });

        // ── Individual package generation jobs ───────────────────────────────
        Schema::create('deployment_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->string('repo');
            $table->string('project_name');
            $table->string('environment', 20);
            $table->string('base_version', 100);
            $table->string('head_version', 100);
            $table->string('package_name');
            $table->enum('status', ['queued', 'running', 'completed', 'failed', 'cancelled'])
                  ->default('queued')->index();
            $table->unsignedInteger('queue_order')->nullable();
            $table->json('progress')->nullable();          // stage fields snapshot
            $table->string('message', 500)->nullable();    // last human-readable message
            $table->json('result_json')->nullable();       // success payload
            $table->string('zip_size', 50)->nullable();
            $table->string('zip_sha256', 64)->nullable();
            $table->string('targz_size', 50)->nullable();
            $table->string('targz_sha256', 64)->nullable();
            $table->text('error_message')->nullable();     // failure reason
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_jobs');
        Schema::dropIfExists('deployment_batches');
    }
};
