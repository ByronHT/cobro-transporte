<?php

namespace Database\Factories;

use App\Models\Ruta;
use Illuminate\Database\Eloquent\Factories\Factory;

class RutaFactory extends Factory
{
    protected $model = Ruta::class;

    public function definition()
    {
        return [
            'nombre' => 'Línea ' . $this->faker->unique()->numberBetween(1, 100),
            'descripcion' => $this->faker->sentence(6),
            'tarifa_base' => $this->faker->randomFloat(2, 1.50, 3.00),
        ];
    }
}
