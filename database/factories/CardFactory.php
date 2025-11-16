<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardFactory extends Factory
{
    protected $model = Card::class;

    public function definition()
    {
        return [
            'uid' => strtoupper($this->faker->unique()->bothify('???###???')),
            'balance' => $this->faker->randomFloat(2, 0, 100),
            'passenger_id' => User::factory()->create(['role' => 'passenger'])->id,
            'active' => true,
        ];
    }
}
