<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_hours', function (Blueprint $table): void {
            if (! Schema::hasColumn('business_hours', 'break_opens_at')) {
                $table->time('break_opens_at')->nullable()->after('closes_at');
            }

            if (! Schema::hasColumn('business_hours', 'break_closes_at')) {
                $table->time('break_closes_at')->nullable()->after('break_opens_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_hours', function (Blueprint $table): void {
            if (Schema::hasColumn('business_hours', 'break_closes_at')) {
                $table->dropColumn('break_closes_at');
            }

            if (Schema::hasColumn('business_hours', 'break_opens_at')) {
                $table->dropColumn('break_opens_at');
            }
        });
    }
};
