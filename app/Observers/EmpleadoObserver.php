<?php

namespace App\Observers;

use App\Models\Empleado;
use App\Models\Vacaciones;
use App\Models\CicloServicio;
use App\Models\EstadoSolicitud;
use App\Models\VacacionesOfficiales;
use Carbon\Carbon;
use App\Services\VacacionesService; // Necesitaremos el servicio para la lógica de negocio

class EmpleadoObserver
{
    protected $vacacionesService;

    public function __construct(VacacionesService $vacacionesService)
    {
        $this->vacacionesService = $vacacionesService;
    }

    /**
     * Handle the Empleado "created" event.
     * Este método NO asignará vacaciones al inicio, solo se activa el "updated" para aniversarios.
     * Si necesitas una inicialización especial al crear el empleado, podrías considerarlo aquí,
     * pero la regla es que "no tiene vacaciones si no ha cumplido el año".
     *
     * @param  \App\Models\Empleado  $empleado
     * @return void
     */
    public function created(Empleado $empleado)
    {
        // No hacemos nada aquí al crear el empleado.
        // Las vacaciones se asignan al cumplir el aniversario (en el 'updated' o un comando periódico).
    }

    /**
     * Handle the Empleado "updated" event.
     * Aquí es donde revisaremos si el empleado ha cumplido un año o un aniversario.
     *
     * @param  \App\Models\Empleado  $empleado
     * @return void
     */
    public function updated(Empleado $empleado)
    {
        // Solo nos interesa si la fecha actual es un aniversario de su fecha de ingreso
        // y si el empleado realmente cumple el aniversario en este momento.

        $fechaIngreso = Carbon::parse($empleado->fecha_ingreso);
        $hoy = Carbon::now();

        // Calcular la antigüedad en años completos
        $antiguedadAnios = $fechaIngreso->diffInYears($hoy);

        // Verificar si es el día de su aniversario en el año actual y si ha cumplido al menos 1 año
        // Y que no hayamos creado ya el registro ASIGNADO para el ciclo actual.
        if ($antiguedadAnios >= 1 && $hoy->month === $fechaIngreso->month && $hoy->day === $fechaIngreso->day) {
            // Verificar si ya existe un registro ASIGNADO para el ciclo actual (año de antigüedad).
            // Esto evita duplicados si el observer se dispara varias veces el mismo día.
            $cicloActual = CicloServicio::firstOrCreate([
                'empleado_id' => $empleado->id,
                'anio' => $hoy->year, // El año en que se cumple el aniversario
            ]);

            $existingAssignedRecord = Vacaciones::where('empleado_id', $empleado->id)
                                                ->where('ciclo_servicio_id', $cicloActual->id)
                                                ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'ASIGNADO'))
                                                ->first();

            if (!$existingAssignedRecord) {
                // Si no existe un registro ASIGNADO para este año, procedemos a crearlo.
                // Usamos el VacacionesService para la lógica de negocio.
                try {
                    // Calculamos los días que arrastraría del año anterior, si aplica
                    $diasArrastradosDelAnioAnterior = $this->vacacionesService->getDiasArrastradosDelCicloAnterior($empleado->id, $hoy->year);

                    // Calculamos los días base por antigüedad para este nuevo ciclo
                    $diasBasePorAntiguedad = $this->vacacionesService->calcularDiasBasePorAntiguedad($empleado->id);

                    // Obtener el estado 'ASIGNADO'
                    $estadoAsignado = EstadoSolicitud::where('estado', 'ASIGNADO')->firstOrFail();

                    // Crear el registro de asignación para el nuevo ciclo
                    Vacaciones::create([
                        'empleado_id'               => $empleado->id,
                        'ciclo_servicio_id'         => $cicloActual->id,
                        'dias_vacaciones_totales'   => $diasBasePorAntiguedad + $diasArrastradosDelAnioAnterior,
                        'dias_vacaciones_arrastrados' => $diasArrastradosDelAnioAnterior,
                        'dias_vacaciones_disfrutados' => 0,
                        'dias_vacaciones_solicitados' => 0,
                        'dias_vacaciones_disponibles' => $diasBasePorAntiguedad + $diasArrastradosDelAnioAnterior,
                        'fecha_solicitud'           => $hoy, // La fecha de asignación es hoy
                        'fecha_inicio'              => null, // La fecha de inicio de vacaciones se define al solicitar
                        // las vacaciones, no al asignarlas anualmente.
                        'fecha_fin'                 => null,
                        'estado_solicitud_id'       => $estadoAsignado->id,
                        'observaciones'             => "Asignación anual de vacaciones por aniversario de ingreso para el año {$hoy->year}.",
                    ]);

                    // Opcional: Log o notificación
                    \Log::info("Vacaciones anuales asignadas a empleado {$empleado->id} para el año {$hoy->year}.");

                } catch (\Exception $e) {
                    \Log::error("Error al asignar vacaciones anuales a empleado {$empleado->id}: " . $e->getMessage());
                    // Dependiendo de tu estrategia de errores, podrías re-lanzar o manejar de otra forma.
                }
            }
        }
    }

    /**
     * Handle the Empleado "deleted" event.
     *
     * @param  \App\Models\Empleado  $empleado
     * @return void
     */
    public function deleted(Empleado $empleado)
    {
        // ... (Si necesitas limpiar registros de vacaciones al eliminar un empleado)
    }

    // Puedes añadir otros métodos como `restored`, `forceDeleted` si los necesitas.
}
