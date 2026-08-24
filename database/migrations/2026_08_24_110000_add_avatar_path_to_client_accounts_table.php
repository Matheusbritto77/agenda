<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_accounts') && ! Schema::hasColumn('client_accounts', 'avatar_path')) {
            Schema::table('client_accounts', function (Blueprint $table): void {
                $table->string('avatar_path')->nullable()->after('phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('client_accounts') && Schema::hasColumn('client_accounts', 'avatar_path')) {
            Schema::table('client_accounts', function (Blueprint $table): void {
                $table->dropColumn('avatar_path');
            });
        }
    }
};
