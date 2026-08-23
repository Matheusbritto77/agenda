<?php

namespace Tests\Feature\Admin;

use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_create_update_and_delete_expenses(): void
    {
        $tenant = User::factory()->create();

        // 1. Create expense
        $response = $this->actingAs($tenant)->post(route('admin.financial.transactions.store'), [
            'type' => 'expense',
            'category' => 'utilidades',
            'title' => 'Conta de Luz Enel',
            'amount' => 250.50,
            'due_date' => '2026-08-30',
            'status' => 'pending',
            'payment_method' => 'pix',
        ]);

        $response->assertRedirect(route('admin.financial.index'));

        $this->assertDatabaseHas('financial_transactions', [
            'user_id' => $tenant->id,
            'title' => 'Conta de Luz Enel',
            'amount' => 250.50,
            'status' => 'pending',
        ]);

        $transaction = FinancialTransaction::where('user_id', $tenant->id)->first();

        // 2. Toggle status to paid
        $toggleResponse = $this->actingAs($tenant)->patch(route('admin.financial.transactions.toggle-status', $transaction->id));
        $toggleResponse->assertRedirect(route('admin.financial.index'));

        $transaction->refresh();
        $this->assertEquals('paid', $transaction->status);
        $this->assertNotNull($transaction->paid_at);

        // 3. Update expense
        $updateResponse = $this->actingAs($tenant)->put(route('admin.financial.transactions.update', $transaction->id), [
            'type' => 'expense',
            'category' => 'utilidades',
            'title' => 'Conta de Luz Enel - Corrigida',
            'amount' => 280.00,
            'due_date' => '2026-08-30',
            'status' => 'paid',
            'payment_method' => 'pix',
        ]);

        $updateResponse->assertRedirect(route('admin.financial.index'));
        $transaction->refresh();
        $this->assertEquals('Conta de Luz Enel - Corrigida', $transaction->title);
        $this->assertEquals(280.00, $transaction->amount);

        // 4. Delete expense
        $deleteResponse = $this->actingAs($tenant)->delete(route('admin.financial.transactions.destroy', $transaction->id));
        $deleteResponse->assertRedirect(route('admin.financial.index'));

        $this->assertDatabaseMissing('financial_transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_tenant_isolation_for_financial_transactions(): void
    {
        $tenantA = User::factory()->create();
        $tenantB = User::factory()->create();

        $transactionA = FinancialTransaction::create([
            'user_id' => $tenantA->id,
            'type' => 'expense',
            'category' => 'aluguel',
            'title' => 'Aluguel Barbearia A',
            'amount' => 1500.00,
            'due_date' => '2026-08-30',
            'status' => 'pending',
        ]);

        // Tenant B cannot edit Tenant A's transaction (scoped out to 404 or 403)
        $response = $this->actingAs($tenantB)->put(route('admin.financial.transactions.update', $transactionA->id), [
            'type' => 'expense',
            'category' => 'aluguel',
            'title' => 'Hack Attempt',
            'amount' => 10.00,
            'due_date' => '2026-08-30',
            'status' => 'pending',
        ]);

        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]));

        // Tenant B cannot delete Tenant A's transaction
        $deleteResponse = $this->actingAs($tenantB)->delete(route('admin.financial.transactions.destroy', $transactionA->id));
        $this->assertTrue(in_array($deleteResponse->getStatusCode(), [403, 404]));
    }
}
