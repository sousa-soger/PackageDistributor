<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ** Should be taken from GitLab Repo listing */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->string('version_name');
            $table->enum('commit_type', ['tag', 'branch', 'commit']);
            $table->string('app_name');
            $table->boolean('is_active')->default(false);
            $table->enum('update_type', ['feature', 'bug fix', 'hot fix', 'performance']);
            $table->text('release_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('versions');
    }
};
