<?php

namespace Database\Factories;

use App\Models\BusLocation;
use App\Models\Bus;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusLocationFactory extends Factory
{
    protected $model = BusLocation::class;

    public function definition()
    {
        return [
            'bus_id' => Bus::factory(),
            'trip_id' => Trip::factory(),
            'driver_id' => User::factory()->create(['role' => 'driver'])->id,
            'latitude' => $this->faker->latitude(-18, -17),
            'longitude' => $this->faker->longitude(-64, -63),
            'speed' => $this->faker->randomFloat(1, 0, 80),
            'heading' => $this->faker->randomFloat(1, 0, 360),
            'accuracy' => $this->faker->randomFloat(1, 5, 20),
            'recorded_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'is_active' => true,
        ];
    }
}
