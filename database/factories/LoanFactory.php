<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            // TODO: Complete factory
            'user_id' => fn () => User::factory()->create(),
            'amount' => $this->faker->numberBetween(1000, 10000),
            'terms' => $this->faker->numberBetween(1, 12),
            'outstanding_amount' => function (array $att) {
                return $att['amount'];
            },
            'currency_code' => Loan::CURRENCY_VND,
            'processed_at' => $this->faker->dateTimeThisMonth(),
            'status' => Loan::STATUS_DUE,
        ];
    }
}
