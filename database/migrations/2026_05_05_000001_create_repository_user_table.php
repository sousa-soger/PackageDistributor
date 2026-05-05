<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('repository_user')) {
            return;
        }

        Schema::create('repository_user', function (Blueprint $table) {
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 40)->default('ldap');
            $table->string('ldap_identifier')->nullable();
            $table->string('role', 40)->default('viewer');
            $table->timestamps();

            $table->unique(['repository_id', 'user_id']);
            $table->index(['user_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repository_user');
    }
};
