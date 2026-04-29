<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ldap_username')->nullable()->after('email')->unique();
            $table->string('ldap_display_name')->nullable()->after('ldap_username');
            $table->mediumText('ldap_photo')->nullable()->after('ldap_display_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['ldap_username']);
            $table->dropColumn(['ldap_username', 'ldap_display_name', 'ldap_photo']);
        });
    }
};
