<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Provider: github | gitlab | company-server | local-pc
            $table->enum('provider', ['github', 'gitlab', 'company-server', 'local-pc']);

            // The repo identifier (owner/repo for GitHub/GitLab, or a label for others)
            $table->string('name');                 // e.g. "atlas/web-storefront"
            $table->string('display_name')->nullable(); // friendly name override
            $table->string('url')->nullable();      // full clone URL or server URL

            // For GitHub / GitLab OAuth repos
            $table->string('external_id')->nullable();   // provider's repo ID
            $table->string('default_branch')->default('main');

            // Cached branch/tag lists (refreshed on demand)
            $table->json('branches')->nullable();
            $table->json('tags')->nullable();

            // Status: connected | expired | needs-auth
            $table->enum('status', ['connected', 'expired', 'needs-auth'])->default('connected');

            // Optional: company-server / local-pc specific fields
            $table->string('server_host')->nullable();
            $table->string('server_path')->nullable();
            $table->enum('server_protocol', ['SSH', 'SFTP', 'HTTP', 'HTTPS'])->nullable();
            $table->string('username')->nullable();
            $table->text('access_token')->nullable(); // encrypted in model

            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // A user cannot add the same repo name+provider twice
            $table->unique(['user_id', 'provider', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};