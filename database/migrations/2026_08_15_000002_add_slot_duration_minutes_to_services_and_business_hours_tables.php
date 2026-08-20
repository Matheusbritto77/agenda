<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'slot_duration_minutes')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->unsignedSmallInteger('slot_duration_minutes')
                    ->default(30)
                    ->after('duration_minutes');
            });
        }

        if (! Schema::hasColumn('business_hours', 'slot_duration_minutes')) {
            Schema::table('business_hours', function (Blueprint $table): void {
                $table->unsignedSmallInteger('slot_duration_minutes')
                    ->default(30)
                    ->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'slot_duration_minutes')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->dropColumn('slot_duration_minutes');
            });
        }

        if (Schema::hasColumn('business_hours', 'slot_duration_minutes')) {
            Schema::table('business_hours', function (Blueprint $table): void {
                $table->dropColumn('slot_duration_minutes');
            });
        }
    }
};
