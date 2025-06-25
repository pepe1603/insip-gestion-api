<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Asegúrate de importar tu modelo User
use Illuminate\Support\Facades\Hash; // Para hashear las contraseñas

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuario Administrador
        User::create([
            'name' => 'Admin User Pepe',
            'email' => 'jose_000316@hotmail.com',
            'password' => Hash::make('12345'), // Contraseña simple para pruebas
            'role' => 'admin', // Asegúrate de que este rol exista y coincida con tu Gate
        ]);

        User::create([
            'name' => 'Admin User JC',
            'email' => 'b180022@unach.mx',
            'password' => Hash::make('12345'), // Contraseña simple para pruebas
            'role' => 'supervisor', // Asegúrate de que este rol exista y coincida con tu Gate
        ]);

        // Usuario Supervisor
        User::create([
            'name' => 'Supervisor User',
            'email' => 'supervisor@example.com',
            'password' => Hash::make('password'),
            'role' => 'supervisor', // Asume que tienes este rol
        ]);

        // Usuario Empleado
        User::create([
            'name' => 'Employee User',
            'email' => 'empleado@example.com',
            'password' => Hash::make('password'),
            'role' => 'empleado', // Asume que tienes este rol
        ]);

        // Si tienes otros roles, añádelos aquí:
        // User::create([
        //     'name' => 'Otro Rol User',
        //     'email' => 'otro@example.com',
        //     'password' => Hash::make('password'),
        //     'role' => 'otro_rol',
        // ]);

        // También puedes usar Factories para crear muchos usuarios de golpe:
        // User::factory()->count(50)->create();
    }
}
