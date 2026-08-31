<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointment_flow_logs')) {
            Schema::create('appointment_flow_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event_type', 50)->index(); // booking_created, approval_requested, whatsapp_enqueued, email_dispatched, reminder_scheduled, appointment_approved, appointment_cancelled, payment_pix_generated, etc.
                $table->string('level', 20)->default('info')->index(); // info, success, warning, error
                $table->string('channel', 20)->default('system')->index(); // whatsapp, email, system, payment
                $table->string('title');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_flow_logs');
    }
};
