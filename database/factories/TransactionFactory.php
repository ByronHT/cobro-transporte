<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Card;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'type' => 'fare',
            'card_id' => Card::factory(),
            'trip_id' => Trip::factory(),
            'amount' => $this->faker->randomFloat(2, 1.50, 3.00),
            'description' => $this->faker->sentence(),
        ];
    }
}
