<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoAsistencia;

class TipoAsistenciaSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'nombre' => 'PRESENTE',
                'descripcion' => 'El empleado asistió puntualmente y cumplió con su jornada laboral sin inconvenientes ni retardos.'
            ],
            [
                'nombre' => 'AUSENTE',
                'descripcion' => 'El empleado no asistió a su jornada laboral y no presentó una justificación válida. Se considera una falta injustificada.'
            ],
            [
                'nombre' => 'PERMISO',
                'descripcion' => 'El empleado se ausentó con autorización previa, por motivos personales, médicos o institucionales. Esta ausencia es considerada justificada.'
            ],
            [
                'nombre' => 'LICENCIA',
                'descripcion' => 'El empleado se encuentra ausente por una causa prolongada y justificada, como maternidad, enfermedad o licencia especial aprobada.'
            ],
            [
                'nombre' => 'EN_VACACIONES',
                'descripcion' => 'El empleado se encuentra oficialmente en su período de vacaciones, según lo establecido en la política interna de la empresa.'
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoAsistencia::firstOrCreate(['nombre' => $tipo['nombre']], $tipo);
        }
    }
}

//caragr Registra el seeder en DatabaseSeeder.php y luego ejecuraar comando: php artisan db:seed

