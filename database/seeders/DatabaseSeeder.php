<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSender;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        $this->call([
            TipoAsistenciaSeeder::class,
            DepartamentoSeeder::class, // <-- Agregar mas seeders
            EstadoSolicitudSeeder::class,
            VacacionesOficialesSeeder::class,
            UserSeeder::class,
        ]);

        // User::factory(10)->create();


        /*
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        */
    }
}
