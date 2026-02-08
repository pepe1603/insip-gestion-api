<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VacacionesOfficiales;

class VacacionesOficialesSeeder extends Seeder
{
    public function run(): void
    {
        $vacaciones = [
            ['tiempo_servicio' => '1', 'dias_vacaciones' => 12],
            ['tiempo_servicio' => '2', 'dias_vacaciones' => 14],
            ['tiempo_servicio' => '3', 'dias_vacaciones' => 16],
            ['tiempo_servicio' => '4', 'dias_vacaciones' => 18],
            ['tiempo_servicio' => '5', 'dias_vacaciones' => 20],
            ['tiempo_servicio' => '06 a 10', 'dias_vacaciones' => 22],
            ['tiempo_servicio' => '11 a 15', 'dias_vacaciones' => 24],
            ['tiempo_servicio' => '16 a 20', 'dias_vacaciones' => 26],
            ['tiempo_servicio' => '21 a 25', 'dias_vacaciones' => 28],
            ['tiempo_servicio' => '26 a 30', 'dias_vacaciones' => 30],
        ];

        foreach ($vacaciones as $entry) {
            VacacionesOfficiales::firstOrCreate(
                ['tiempo_servicio' => $entry['tiempo_servicio']],
                $entry
            );
        }
    }
}
