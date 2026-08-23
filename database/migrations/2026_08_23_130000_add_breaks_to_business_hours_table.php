<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_hours', function (Blueprint $table): void {
            if (! Schema::hasColumn('business_hours', 'breaks')) {
                $table->json('breaks')->nullable()->after('break_closes_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_hours', function (Blueprint $table): void {
            if (Schema::hasColumn('business_hours', 'breaks')) {
                $table->dropColumn('breaks');
            }
        });
    }
};
