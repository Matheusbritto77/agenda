<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_member_id')->nullable()->constrained('team_members')->nullOnDelete();
            $table->string('type')->default('expense'); // 'expense' or 'income'
            $table->string('category')->default('outros'); // 'aluguel', 'utilidades', 'fornecedores', 'marketing', 'pessoal', 'impostos', 'venda_produtos', 'outros'
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'paid', 'cancelled'
            $table->string('payment_method')->nullable(); // 'pix', 'credit_card', 'debit_card', 'boleto', 'cash', 'bank_transfer'
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'status']);
            $table->index(['user_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
