<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_reviews')) {
            Schema::create('company_reviews', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('client_account_id')->constrained('client_accounts')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->boolean('is_public')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'client_account_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_reviews');
    }
};
