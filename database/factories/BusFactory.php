<?php

namespace Database\Factories;

use App\Models\Bus;
use App\Models\Ruta;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusFactory extends Factory
{
    protected $model = Bus::class;

    public function definition()
    {
        return [
            'plate' => strtoupper($this->faker->unique()->bothify('???-###')),
            'code' => strtoupper($this->faker->unique()->bothify('BUS###')),
            'brand' => $this->faker->randomElement(['Mercedes-Benz', 'Volvo', 'Scania', 'Volkswagen']),
            'model' => $this->faker->randomElement(['O500', 'B11R', 'K380', 'Volksbus 17.230']),
            'ruta_id' => Ruta::factory(),
        ];
    }
}
