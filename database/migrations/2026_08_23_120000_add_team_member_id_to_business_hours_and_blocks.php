<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('business_hours') && ! Schema::hasColumn('business_hours', 'team_member_id')) {
            Schema::table('business_hours', function (Blueprint $table) {
                $table->foreignId('team_member_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('team_members')
                    ->nullOnDelete();

                $table->index(['user_id', 'team_member_id', 'day_of_week', 'is_active'], 'bh_user_member_day_active_idx');
            });
        }

        if (Schema::hasTable('blocked_time_slots') && ! Schema::hasColumn('blocked_time_slots', 'team_member_id')) {
            Schema::table('blocked_time_slots', function (Blueprint $table) {
                $table->foreignId('team_member_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('team_members')
                    ->nullOnDelete();

                $table->index(['user_id', 'team_member_id', 'starts_at', 'ends_at'], 'bts_user_member_range_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('business_hours') && Schema::hasColumn('business_hours', 'team_member_id')) {
            Schema::table('business_hours', function (Blueprint $table) {
                $table->dropIndex('bh_user_member_day_active_idx');
                $table->dropConstrainedForeignId('team_member_id');
            });
        }

        if (Schema::hasTable('blocked_time_slots') && Schema::hasColumn('blocked_time_slots', 'team_member_id')) {
            Schema::table('blocked_time_slots', function (Blueprint $table) {
                $table->dropIndex('bts_user_member_range_idx');
                $table->dropConstrainedForeignId('team_member_id');
            });
        }
    }
};
