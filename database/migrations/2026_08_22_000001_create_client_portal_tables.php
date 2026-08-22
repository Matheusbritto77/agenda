<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->boolean('must_reset_password')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('client_account_id')
                ->nullable()
                ->after('user_id')
                ->constrained('client_accounts')
                ->nullOnDelete()
                ->index();
        });

        Schema::create('appointment_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('client_account_id')->constrained('client_accounts')->cascadeOnDelete()->index();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_reviews');

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('client_account_id');
        });

        Schema::dropIfExists('client_accounts');
    }
};
