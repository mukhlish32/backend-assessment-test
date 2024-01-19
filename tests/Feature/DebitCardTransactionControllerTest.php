<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use App\Policies\DebitCardTransactionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
        DebitCardTransaction::factory(5)->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        $response = $this->get("/api/debit-card-transactions?debit_card_id=" . $this->debitCard->id);
        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        DebitCardTransaction::factory(3)->create(['debit_card_id' => $otherDebitCard->id]);

        $response = $this->get("/api/debit-card-transactions?debit_card_id=" . $otherDebitCard->id);
        $response->assertStatus(403); // Forbidden
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $transactionData = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 2,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR
        ];

        $response = $this->post('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $transactionData['amount'],
            'currency_code' => $transactionData['currency_code'],
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $transactionData = [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 2,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR
        ];

        $response = $this->post('/api/debit-card-transactions', $transactionData);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => $transactionData['amount'],
            'currency_code' => $transactionData['currency_code'],
        ]);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
        ]);

        $response = $this->get("/api/debit-card-transactions/{$debitCardTransaction->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'amount' => $debitCardTransaction->amount,
            'currency_code' => $debitCardTransaction->currency_code
        ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);
    
        $debitCardTransaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id,
        ]);
    
        $this->assertFalse(app(DebitCardTransactionPolicy::class)->view($this->user, $debitCardTransaction));
        // Note: Jika menjalankan API di bawah dengan menggunakan user lain, menghasilkan Error : This action is unauthorized.
        $response = $this->get("/api/debit-card-transactions/{$debitCardTransaction->id}");
        $response->assertStatus(403)
            ->assertJson([
                'error' => 'unauthorized'
            ]);
        $response->assertJsonMissing([
            'amount' => $debitCardTransaction->amount,
            'currency_code' => $debitCardTransaction->currency_code
        ]);
    }

    // Extra bonus for extra tests :)
}
