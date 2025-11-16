<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bus;
use App\Models\User;
use App\Models\Ruta;
use App\Models\Trip;
use App\Models\BusLocation;
use App\Models\Transaction;
use App\Models\Card;

class RealtimeTestSeeder extends Seeder
{
    /**
     * Crear datos de prueba para el sistema de tiempo real
     */
    public function run()
    {
        echo "🚀 Creando datos de prueba para Tiempo Real...\n";

        // Obtener recursos necesarios
        $bus = Bus::first();
        $driver = User::where('role', 'driver')->first();
        $ruta = Ruta::first();

        if (!$bus || !$driver || !$ruta) {
            echo "❌ Error: No hay buses, choferes o rutas en la base de datos\n";
            echo "   Por favor, crea estos recursos primero.\n";
            return;
        }

        // Verificar si ya hay un viaje activo para este bus
        $existingTrip = Trip::where('bus_id', $bus->id)
            ->whereNull('fin')
            ->first();

        if ($existingTrip) {
            echo "⚠️  Ya existe un viaje activo para el bus {$bus->plate}\n";
            echo "   Usando viaje existente ID: {$existingTrip->id}\n";
            $trip = $existingTrip;
        } else {
            // Crear viaje activo
            $trip = Trip::create([
                'fecha' => now()->format('Y-m-d'),
                'ruta_id' => $ruta->id,
                'bus_id' => $bus->id,
                'driver_id' => $driver->id,
                'inicio' => now()->subHour(),
                'fin' => null,
                'reporte' => 'Viaje en curso - Ruta normal'
            ]);
            echo "✅ Viaje activo creado: ID {$trip->id}\n";
        }

        // Crear algunas transacciones de prueba
        $card = Card::first();
        if ($card) {
            for ($i = 0; $i < 5; $i++) {
                Transaction::create([
                    'card_id' => $card->id,
                    'trip_id' => $trip->id,
                    'amount' => 2.00,
                    'created_at' => now()->subMinutes(rand(10, 50))
                ]);
            }
            echo "✅ 5 transacciones de prueba creadas\n";
        }

        // Limpiar ubicaciones antiguas de este bus
        BusLocation::where('bus_id', $bus->id)->delete();

        // Crear ubicaciones GPS simuladas (ruta en Santa Cruz, Bolivia)
        // Simula un recorrido por la ciudad
        $locations = [
            [-17.7833, -63.1821, 45.5, 90],   // Punto 1
            [-17.7843, -63.1831, 48.2, 92],   // Punto 2
            [-17.7853, -63.1841, 50.1, 88],   // Punto 3
            [-17.7863, -63.1851, 52.3, 91],   // Punto 4 (más reciente)
        ];

        $minutesAgo = 15;
        foreach ($locations as $index => $loc) {
            BusLocation::create([
                'bus_id' => $bus->id,
                'trip_id' => $trip->id,
                'driver_id' => $driver->id,
                'latitude' => $loc[0],
                'longitude' => $loc[1],
                'speed' => $loc[2],
                'heading' => $loc[3],
                'accuracy' => 10.0,
                'recorded_at' => now()->subMinutes($minutesAgo - ($index * 3)),
                'is_active' => true
            ]);
        }

        echo "✅ 4 ubicaciones GPS creadas\n";
        echo "\n📊 Resumen:\n";
        echo "   Bus: {$bus->plate}\n";
        echo "   Chofer: {$driver->name}\n";
        echo "   Ruta: {$ruta->nombre}\n";
        echo "   Ganancias: Bs " . number_format($trip->transactions()->sum('amount'), 2) . "\n";
        echo "\n🗺️  Ahora puedes ver el bus en el mapa de Tiempo Real!\n";
    }
}
