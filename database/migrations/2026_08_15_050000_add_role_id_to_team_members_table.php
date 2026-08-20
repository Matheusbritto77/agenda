<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('team_members') && ! Schema::hasColumn('team_members', 'role_id')) {
            Schema::table('team_members', function (Blueprint $table): void {
                $table->string('role_id')->nullable()->default('professional')->after('job_title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('team_members') && Schema::hasColumn('team_members', 'role_id')) {
            Schema::table('team_members', function (Blueprint $table): void {
                $table->dropColumn('role_id');
            });
        }
    }
};
