<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->get('api/debit-cards');
        $response->assertStatus(200);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $otherUser = User::factory()->create();
        DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->get('api/debit-cards');
        $response->assertStatus(200);
        $response->assertJsonMissing([
            'data' => [
                [
                    'user_id' => $otherUser->id
                ]
            ]
        ]);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $debitCardData = [
            'type' => 'VISA',
        ];

        $response = $this->post('api/debit-cards', $debitCardData);
        $response->assertStatus(201);
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
            'type' => $debitCardData['type']
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->get("api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $debitCard->id
        ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->get("api/debit-cards/{$debitCard->id}");
        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => now(),
        ]);

        $response = $this->put("api/debit-cards/{$debitCard->id}", ['is_active' => true]);

        $response->assertStatus(200);
        $this->assertTrue($debitCard->fresh()->isActive);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->put("api/debit-cards/{$debitCard->id}", ['is_active' => false]);

        $response->assertStatus(000);
        $this->assertFalse($debitCard->fresh()->isActive);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->put("api/debit-cards/{$debitCard->id}", ['is_active' => 'invalid_value']);
        $response->assertStatus(302);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->delete("api/debit-cards/{$debitCard->id}");
        $response->assertStatus(204);
        $this->assertSoftDeleted('debit_cards', ['id' => $debitCard->id]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCard->id
        ]);

        $response = $this->delete("api/debit-cards/{$debitCard->id}");
        $response->assertStatus(403);
    }

    // Extra bonus for extra tests :)
}
