<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\Bus;
use App\Models\Ruta;
use App\Models\User;
use App\Models\Card;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition()
    {
        $inicio = $this->faker->dateTimeBetween('-1 week', 'now');
        $fin = $this->faker->boolean(70) ? $this->faker->dateTimeBetween($inicio, 'now') : null;

        return [
            'fecha' => $inicio->format('Y-m-d'),
            'ruta_id' => Ruta::factory(),
            'bus_id' => Bus::factory(),
            'driver_id' => User::factory()->create(['role' => 'driver'])->id,
            'inicio' => $inicio,
            'fin' => $fin,
            'reporte' => $fin ? 'Viaje completado sin incidentes' : 'Viaje concluido sin novedades',
        ];
    }
}
