<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Departamento;

class DepartamentoSeeder extends Seeder
{
    public function run(): void
    {
        $departamentos = [
            [
                'nombre' => 'Recursos Humanos',
                'descripcion' => 'Encargado de gestionar personal, reclutamiento, nómina y relaciones laborales.'
            ],
            [
                'nombre' => 'Tecnología de la Información',
                'descripcion' => 'Gestiona infraestructura tecnológica, soporte técnico y desarrollo de software.'
            ],
            [
                'nombre' => 'Finanzas',
                'descripcion' => 'Responsable del control financiero, presupuestos, pagos y contabilidad.'
            ],
            [
                'nombre' => 'Operaciones',
                'descripcion' => 'Coordina la producción y procesos logísticos para garantizar la eficiencia operativa.'
            ],
            [
                'nombre' => 'Ventas y Marketing',
                'descripcion' => 'Encargado de la promoción, posicionamiento y ventas de productos o servicios.'
            ],
        ];

        foreach ($departamentos as $depto) {
            Departamento::firstOrCreate(['nombre' => $depto['nombre']], $depto);
        }
    }
}
