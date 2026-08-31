<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('email_enabled')->default(true);
            $table->boolean('whatsapp_enabled')->default(true);
            $table->boolean('require_manual_confirmation')->default(false);
            $table->boolean('reminder_enabled')->default(true);
            $table->integer('reminder_time_value')->default(2);
            $table->string('reminder_time_unit')->default('hours'); // 'minutes', 'hours', 'days'
            $table->boolean('notify_client_on_booking')->default(true);
            $table->boolean('notify_staff_on_booking')->default(true);
            $table->boolean('notify_client_on_confirmation')->default(true);
            $table->boolean('notify_client_on_cancellation')->default(true);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
