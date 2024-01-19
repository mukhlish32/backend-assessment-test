<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            // TODO: Complete factory
            'loan_id' => fn () => Loan::factory()->create(),
            'amount' => $this->faker->numberBetween(1000, 10000),
            'status' => ScheduledRepayment::STATUS_DUE,
            'outstanding_amount' => function (array $att) {
                if ($att['status'] == ScheduledRepayment::STATUS_REPAID) {
                    return 0;
                }
                return $att['amount'];
            },
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => $this->faker->dateTimeThisMonth(),
        ];
    }
}
