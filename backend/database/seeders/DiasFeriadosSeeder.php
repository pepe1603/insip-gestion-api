<?php

namespace Database\Seeders;

use App\Models\DiasFeriados;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DiasFeriadosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DiasFeriados::insert([
            ['fecha' => Carbon::parse('2025-01-01'), 'descripcion' => 'Año Nuevo'],
            ['fecha' => Carbon::parse('2025-02-03'), 'descripcion' => 'Día de la Constitución (movible)'],
            ['fecha' => Carbon::parse('2025-03-17'), 'descripcion' => 'Natalicio de Benito Juárez (movible)'],
            ['fecha' => Carbon::parse('2025-05-01'), 'descripcion' => 'Día del Trabajo'],
            ['fecha' => Carbon::parse('2025-09-16'), 'descripcion' => 'Día de la Independencia'],
            ['fecha' => Carbon::parse('2025-11-17'), 'descripcion' => 'Día de la Revolución Mexicana (movible)'],
            ['fecha' => Carbon::parse('2025-12-25'), 'descripcion' => 'Navidad'],
            // Agrega otros días feriados oficiales en México
        ]);
    }
}
