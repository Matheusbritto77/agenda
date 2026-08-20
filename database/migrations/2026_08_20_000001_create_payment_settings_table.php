<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('gateway');
            $table->boolean('is_active')->default(false);
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'gateway']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
