<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EstadoSolicitud;

class EstadoSolicitudSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            [
                'estado' => 'APROBADO',
                'descripcion' => 'La solicitud de vacaciones ha sido revisada y aprobada por el departamento de Recursos Humanos.'
            ],
            [
                'estado' => 'PENDIENTE',
                'descripcion' => 'La solicitud de vacaciones está en espera de revisión y aprobación por Recursos Humanos.'
            ],
            [
                'estado' => 'RECHAZADO',
                'descripcion' => 'La solicitud de vacaciones fue evaluada y rechazada por el área correspondiente.'
            ],
            [
                'estado' => 'NO_SOLICITADA',
                'descripcion' => 'Días de vacaciones no han sido solicitados formalmente por el empleado.'
            ],
        ];

        foreach ($estados as $estado) {
            EstadoSolicitud::firstOrCreate(['estado' => $estado['estado']], $estado);
        }
    }
}
