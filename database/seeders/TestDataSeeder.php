<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Ruta;
use App\Models\Bus;
use App\Models\Card;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        echo "🚀 Creando datos de prueba...\n";

        // 1. RUTAS
        echo "📍 Creando rutas...\n";
        $ruta1 = Ruta::create([
            'nombre' => 'Ruta 1 - Centro',
            'descripcion' => 'Av. Principal - Plaza Central',
            'tarifa_base' => 2.50,
        ]);

        $ruta2 = Ruta::create([
            'nombre' => 'Ruta 2 - Norte',
            'descripcion' => 'Terminal Norte - Universidad',
            'tarifa_base' => 3.00,
        ]);

        $ruta3 = Ruta::create([
            'nombre' => 'Ruta 3 - Sur',
            'descripcion' => 'Mercado Central - Zona Sur',
            'tarifa_base' => 2.00,
        ]);

        // 2. BUSES
        echo "🚌 Creando buses...\n";
        Bus::create([
            'plate' => 'ABC-123',
            'code' => 'BUS001',
            'brand' => 'Mercedes Benz',
            'model' => 'OF-1721',
            'ruta_id' => $ruta1->id,
        ]);

        Bus::create([
            'plate' => 'DEF-456',
            'code' => 'BUS002',
            'brand' => 'Volvo',
            'model' => 'B270F',
            'ruta_id' => $ruta1->id,
        ]);

        Bus::create([
            'plate' => 'GHI-789',
            'code' => 'BUS003',
            'brand' => 'Scania',
            'model' => 'K280',
            'ruta_id' => $ruta2->id,
        ]);

        Bus::create([
            'plate' => 'JKL-012',
            'code' => 'BUS004',
            'brand' => 'Mercedes Benz',
            'model' => 'OF-1722',
            'ruta_id' => $ruta3->id,
        ]);

        // 3. CHOFERES
        echo "👨‍✈️ Creando choferes...\n";
        $driver1 = User::create([
            'name' => 'Juan Pérez',
            'email' => 'driver@test.com',
            'password' => Hash::make('driver123'),
            'role' => 'driver',
            'active' => true,
            'balance' => 0,
        ]);

        $driver2 = User::create([
            'name' => 'María González',
            'email' => 'driver2@test.com',
            'password' => Hash::make('driver123'),
            'role' => 'driver',
            'active' => true,
            'balance' => 0,
        ]);

        // 4. PASAJEROS
        echo "👥 Creando pasajeros...\n";
        $passenger1 = User::create([
            'name' => 'Carlos López',
            'email' => 'passenger@test.com',
            'password' => Hash::make('passenger123'),
            'role' => 'passenger',
            'active' => true,
            'balance' => 50.00,
        ]);

        $passenger2 = User::create([
            'name' => 'Ana Martínez',
            'email' => 'passenger2@test.com',
            'password' => Hash::make('passenger123'),
            'role' => 'passenger',
            'active' => true,
            'balance' => 30.00,
        ]);

        // 5. TARJETAS
        echo "💳 Creando tarjetas RFID...\n";
        Card::create([
            'uid' => 'CARD001',
            'passenger_id' => $passenger1->id,
            'balance' => 50.00,
            'active' => true,
        ]);

        Card::create([
            'uid' => 'CARD002',
            'passenger_id' => $passenger2->id,
            'balance' => 30.00,
            'active' => true,
        ]);

        Card::create([
            'uid' => 'CARD003',
            'passenger_id' => $passenger1->id,
            'balance' => 20.00,
            'active' => true,
        ]);

        echo "\n";
        echo "✅ Datos de prueba creados exitosamente!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📊 RESUMEN:\n";
        echo "   • 3 Rutas\n";
        echo "   • 4 Buses\n";
        echo "   • 2 Choferes\n";
        echo "   • 2 Pasajeros\n";
        echo "   • 3 Tarjetas\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🔑 CREDENCIALES DE ACCESO:\n";
        echo "\n";
        echo "👨‍✈️ CHOFER:\n";
        echo "   Email: driver@test.com\n";
        echo "   Pass:  driver123\n";
        echo "\n";
        echo "👤 PASAJERO:\n";
        echo "   Email: passenger@test.com\n";
        echo "   Pass:  passenger123\n";
        echo "\n";
        echo "💳 TARJETAS:\n";
        echo "   UID: CARD001 (Saldo: $50.00)\n";
        echo "   UID: CARD002 (Saldo: $30.00)\n";
        echo "   UID: CARD003 (Saldo: $20.00)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
}
