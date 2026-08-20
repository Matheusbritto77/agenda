<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('subdomain', 63)->nullable()->unique()->after('email');
            $table->string('custom_domain')->nullable()->unique()->after('subdomain');
            $table->enum('active_domain_type', ['subdomain', 'custom'])->default('subdomain')->after('custom_domain');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['subdomain']);
            $table->dropUnique(['custom_domain']);
            $table->dropColumn(['subdomain', 'custom_domain', 'active_domain_type']);
        });
    }
};
