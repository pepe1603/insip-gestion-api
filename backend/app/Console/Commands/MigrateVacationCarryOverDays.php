<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Empleado;
use App\Models\Vacaciones;
use App\Models\CicloServicio;
use App\Models\VacacionesOfficiales; // Necesario para calcularDiasBasePorAntiguedad
use App\Models\EstadoSolicitud; // Necesario para el estado inicial
use Carbon\Carbon;

class MigrateVacationCarryOverDays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vacaciones:migrate-carry-over';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates historical vacation carry-over days for existing employees.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando la migración de días de vacaciones arrastrados...');

        // Obtener el año actual de la operación (ej. 2025)
        $currentYear = now()->year;

        // Obtener todos los empleados
        $empleados = Empleado::all();

        $processedCount = 0;

        foreach ($empleados as $empleado) {
            $this->info("Procesando empleado: {$empleado->nombre} {$empleado->apellido} (ID: {$empleado->id})");

            // 1. Encontrar o crear el ciclo de servicio actual para el empleado
            $cicloServicioActual = CicloServicio::firstOrCreate([
                'empleado_id' => $empleado->id,
                'anio' => $currentYear,
            ]);

            // 2. Intentar obtener el registro de vacaciones para el empleado en el AÑO ACTUAL.
            // Si ya existe, lo actualizaremos. Si no, lo crearemos.
            $vacacionCurrentYear = Vacaciones::where('empleado_id', $empleado->id)
                                            ->where('ciclo_servicio_id', $cicloServicioActual->id)
                                            ->first();

            // Días base de vacaciones para el año actual por antigüedad (sin arrastres)
            $diasBasePorAntiguedad = $this->calcularDiasBasePorAntiguedad($empleado->id);

            // Días arrastrados del año anterior
            $diasArrastrados = 0;
            $anioAnterior = $currentYear - 1; // Año anterior (ej. 2024)

            // Buscar el último registro de vacaciones APROBADO del AÑO ANTERIOR
            $vacacionAnterior = Vacaciones::where('empleado_id', $empleado->id)
                                        ->whereHas('cicloServicio', fn($q) => $q->where('anio', $anioAnterior))
                                        ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
                                        ->orderByDesc('created_at') // Tomar el último registro si hay varios
                                        ->first();

            if ($vacacionAnterior) {
                $diasNoDisfrutadosAnterior = $vacacionAnterior->dias_vacaciones_totales - $vacacionAnterior->dias_vacaciones_disfrutados;
                if ($diasNoDisfrutadosAnterior > 0) {
                    $diasArrastrados = $diasNoDisfrutadosAnterior;
                    $this->info("  - Días arrastrados del {$anioAnterior}: {$diasArrastrados} días.");
                } else {
                    $this->info("  - No hay días para arrastrar del {$anioAnterior}.");
                }
            } else {
                $this->info("  - No se encontró registro de vacaciones aprobado para el año {$anioAnterior}.");
            }

            // Calcular el total de días de vacaciones para el año actual (base + arrastrados)
            $diasVacacionesTotalesCalculados = $diasBasePorAntiguedad + $diasArrastrados;

            if ($vacacionCurrentYear) {
                // Si el registro ya existe, lo actualizamos
                $vacacionCurrentYear->update([
                    'dias_vacaciones_arrastrados' => $diasArrastrados,
                    'dias_vacaciones_totales' => $diasVacacionesTotalesCalculados,
                    // Recalcular disponibles basados en los días disfrutados YA REGISTRADOS para el AÑO ACTUAL
                    'dias_vacaciones_disponibles' => $diasVacacionesTotalesCalculados - $vacacionCurrentYear->dias_vacaciones_disfrutados,
                ]);
                $this->info("  - Registro de vacaciones del {$currentYear} actualizado.");
            } else {
                // Si no existe, creamos un nuevo registro para el año actual
                // Es importante que este registro refleje los valores correctos
                // y que el `boot` del modelo NO los sobrescriba si ya los estamos configurando.
                // OJO: Si el `boot` del modelo ya se dispara y calcula arrastrados al crear
                // este registro, deberías validar si ya lo hizo o no.
                // Para este comando, vamos a ser explícitos.
                $estadoPendiente = EstadoSolicitud::where('estado', 'PENDIENTE')->first();
                if (!$estadoPendiente) {
                    $this->error('Estado PENDIENTE no encontrado. Asegúrate de que existe en la tabla estados_solicitud.');
                    continue; // Saltar al siguiente empleado
                }

                Vacaciones::create([
                    'empleado_id' => $empleado->id,
                    'ciclo_servicio_id' => $cicloServicioActual->id,
                    'dias_vacaciones_totales' => $diasVacacionesTotalesCalculados,
                    'dias_vacaciones_arrastrados' => $diasArrastrados,
                    'dias_vacaciones_disfrutados' => 0, // Al crear un nuevo registro para un nuevo ciclo, disfrutados es 0
                    'dias_vacaciones_solicitados' => 0, // Idem, solicitados es 0
                    'dias_vacaciones_disponibles' => $diasVacacionesTotalesCalculados,
                    'fecha_solicitud' => now(), // Fecha de inicialización
                    'estado_solicitud_id' => $estadoPendiente->id, // O un estado específico para "inicializado"
                    'observaciones' => 'Registro inicial de vacaciones para el ciclo, incluyendo días arrastrados.',
                ]);
                $this->info("  - Nuevo registro de vacaciones del {$currentYear} creado.");
            }
            $processedCount++;
        }

        $this->info("Migración completada. Se procesaron {$processedCount} empleados.");
        return Command::SUCCESS;
    }

    /**
     * Helper para calcular los días base de vacaciones por antigüedad.
     * Copia de la lógica existente en VacacionesService para este comando.
     */
    private function calcularDiasBasePorAntiguedad($empleadoId)
    {
        $empleado = Empleado::find($empleadoId);
        if (!$empleado) return 0;

        $antiguedad = Carbon::parse($empleado->fecha_ingreso)->diffInYears(now());

        $registro = VacacionesOfficiales::where('tiempo_servicio', '<=', $antiguedad)
            ->orderByDesc('tiempo_servicio')->first();

        return $registro->dias_vacaciones ?? 0;
    }
}
