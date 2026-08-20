<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            try {
                $table->dropUnique('appointments_user_date_time_unique');
            } catch (\Throwable $e) {
                // Ignore if it doesn't exist
            }

            // Add a regular index instead of unique to allow concurrent/re-booked slots
            $table->index(['user_id', 'appointment_date', 'appointment_time'], 'appointments_user_date_time_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            try {
                $table->dropIndex('appointments_user_date_time_index');
            } catch (\Throwable $e) {
            }

            $table->unique(['user_id', 'appointment_date', 'appointment_time'], 'appointments_user_date_time_unique');
        });
    }
};
