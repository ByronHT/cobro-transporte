<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Eliminar usuario existente si existe
        User::where('email', 'admin@cobro.test')->delete();

        // Crear nuevo usuario admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@cobro.test',
            'password' => Hash::make('123456'),
            'role' => 'admin',
            'active' => true,
            'balance' => 0,
        ]);

        echo "✅ Usuario admin creado: admin@cobro.test / 123456\n";
    }
}
