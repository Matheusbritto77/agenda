<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('appointments', function (Blueprint $table): void {
                if (Schema::hasColumn('appointments', 'user_id')) {
                    try {
                        $table->dropUnique('appointments_date_time_unique');
                    } catch (\Throwable $e) {
                    }
                    $table->unique(['user_id', 'appointment_date', 'appointment_time'], 'appointments_user_date_time_unique');
                    $table->index(['user_id', 'appointment_date', 'status', 'appointment_time'], 'appointments_user_date_status_time_index');
                }
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('business_hours', function (Blueprint $table): void {
                if (Schema::hasColumn('business_hours', 'user_id')) {
                    try {
                        $table->dropIndex('business_hours_day_of_week_is_active_index');
                    } catch (\Throwable $e) {
                    }
                    $table->index(['user_id', 'day_of_week', 'is_active', 'opens_at'], 'business_hours_user_day_active_opens_index');
                }
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('blocked_time_slots', function (Blueprint $table): void {
                if (Schema::hasColumn('blocked_time_slots', 'user_id')) {
                    try {
                        $table->dropIndex('blocked_time_slots_starts_at_ends_at_is_active_index');
                    } catch (\Throwable $e) {
                    }
                    $table->index(['user_id', 'starts_at', 'ends_at', 'is_active'], 'blocked_time_slots_user_range_active_index');
                }
            });
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            if (Schema::hasColumn('appointments', 'user_id')) {
                $table->dropUnique('appointments_user_date_time_unique');
                $table->dropIndex('appointments_user_date_status_time_index');
                $table->unique(['appointment_date', 'appointment_time'], 'appointments_date_time_unique');
                $table->index(['appointment_date', 'status', 'appointment_time'], 'appointments_date_status_time_index');
            }
        });

        Schema::table('business_hours', function (Blueprint $table): void {
            if (Schema::hasColumn('business_hours', 'user_id')) {
                $table->dropIndex('business_hours_user_day_active_opens_index');
                $table->index(['day_of_week', 'is_active'], 'business_hours_day_of_week_is_active_index');
            }
        });

        Schema::table('blocked_time_slots', function (Blueprint $table): void {
            if (Schema::hasColumn('blocked_time_slots', 'user_id')) {
                $table->dropIndex('blocked_time_slots_user_range_active_index');
                $table->index(['starts_at', 'ends_at', 'is_active'], 'blocked_time_slots_starts_at_ends_at_is_active_index');
            }
        });
    }
};
