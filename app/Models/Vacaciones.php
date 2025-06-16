<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // Importa Carbon para usar now() y parse()
use App\Exceptions\BusinessException; // Asegúrate de importar tu excepción personalizada

class Vacaciones extends Model
{
    use HasFactory;

    protected $table = 'vacaciones';

    protected $fillable = [
        'empleado_id',
        'ciclo_servicio_id',
        'dias_vacaciones_totales',
        'dias_vacaciones_arrastrados',
        'dias_vacaciones_disfrutados',
        'dias_vacaciones_solicitados',
        'dias_vacaciones_disponibles',
        'fecha_solicitud',
        'fecha_aprobacion',
        'fecha_inicio',
        'fecha_fin',
        'estado_solicitud_id',
        'observaciones',
    ];

    // Relaciones
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function estadoSolicitud()
    {
        return $this->belongsTo(EstadoSolicitud::class);
    }

    public function cicloServicio()
    {
        return $this->belongsTo(CicloServicio::class);
    }

    // Boot para asignar ciclo de servicio automáticamente y calcular días arrastrados y totales
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vacacion) {
            // Asegúrate de que el empleado_id está presente
            if (!$vacacion->empleado_id) {
                // Es preferible lanzar una excepción de un tipo más específico si ya la tienes,
                // como EmpleadoNoEncontradoException si el empleado_id es nulo al llegar aquí.
                throw new BusinessException("El 'empleado_id' es necesario para crear un registro de vacaciones.", 400);
            }

            // 1. Asegurar la asignación del ciclo de servicio actual
            $cicloServicioActual = \App\Models\CicloServicio::firstOrCreate([
                'empleado_id' => $vacacion->empleado_id,
                'anio' => Carbon::now()->year,
            ]);
            $vacacion->ciclo_servicio_id = $cicloServicioActual->id;

            // 2. Determinar 'dias_vacaciones_arrastrados'
            // Solo calculamos los días arrastrados del año anterior si NO se han proporcionado
            // explícitamente al crear la instancia de Vacaciones (ej. por inicializarVacacionesEmpleadoHistorico).
            if (!isset($vacacion->dias_vacaciones_arrastrados)) {
                $anioAnterior = Carbon::now()->subYear()->year;

                $vacacionAnterior = Vacaciones::where('empleado_id', $vacacion->empleado_id)
                                            ->whereHas('cicloServicio', fn($q) => $q->where('anio', $anioAnterior))
                                            ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
                                            ->orderByDesc('created_at') // Tomar el último registro si hay varios del año anterior
                                            ->first();

                $diasNoDisfrutadosAnterior = 0;
                if ($vacacionAnterior) {
                    $diasNoDisfrutadosAnterior = $vacacionAnterior->dias_vacaciones_totales - $vacacionAnterior->dias_vacaciones_disfrutados;
                    // Asegurarse de no arrastrar días negativos
                    if ($diasNoDisfrutadosAnterior < 0) {
                        $diasNoDisfrutadosAnterior = 0;
                    }
                }
                $vacacion->dias_vacaciones_arrastrados = $diasNoDisfrutadosAnterior;
            }

            // 3. Calcular 'dias_vacaciones_totales'
            // Esto siempre se recalcula al crear un nuevo registro de vacaciones para el ciclo.
            // Primero, obtenemos los días base por antigüedad.
            $empleado = \App\Models\Empleado::find($vacacion->empleado_id);
            if (!$empleado) {
                 throw new BusinessException("El empleado con ID {$vacacion->empleado_id} no pudo ser encontrado al calcular días de vacaciones.", 404);
            }
            $antiguedad = Carbon::parse($empleado->fecha_ingreso)->diffInYears(Carbon::now());
            $registroOficial = \App\Models\VacacionesOfficiales::where('tiempo_servicio', '<=', $antiguedad)
                                                                ->orderByDesc('tiempo_servicio')
                                                                ->first();
            $diasBasePorAntiguedad = $registroOficial->dias_vacaciones ?? 0;

            // Sumar días base y días arrastrados (que ya están en $vacacion->dias_vacaciones_arrastrados)
            $vacacion->dias_vacaciones_totales = $diasBasePorAntiguedad + $vacacion->dias_vacaciones_arrastrados;

            // 4. Inicializar 'dias_vacaciones_disfrutados' y 'dias_vacaciones_solicitados' si no están set
            // Estos campos deberían ser 0 al crear un nuevo registro de vacaciones, a menos que sea una solicitud directa.
            if (!isset($vacacion->dias_vacaciones_disfrutados)) {
                $vacacion->dias_vacaciones_disfrutados = 0;
            }
            if (!isset($vacacion->dias_vacaciones_solicitados)) {
                $vacacion->dias_vacaciones_solicitados = 0;
            }

            // 5. Calcular 'dias_vacaciones_disponibles' iniciales
            // Al momento de la creación de un registro base para el ciclo,
            // los días disponibles son los días totales asignados, menos los ya disfrutados.
            // Es crucial que esto se haga DESPUÉS de que total y disfrutados estén set.
            $vacacion->dias_vacaciones_disponibles = $vacacion->dias_vacaciones_totales - $vacacion->dias_vacaciones_disfrutados;

            // Asegurarse de que la fecha de solicitud se establece si no viene definida
            if (!$vacacion->fecha_solicitud) {
                 $vacacion->fecha_solicitud = Carbon::now();
            }

            // Asegurarse de que el estado de solicitud inicial se establece si no viene definida
            if (!$vacacion->estado_solicitud_id) {
                $estadoPendiente = \App\Models\EstadoSolicitud::where('estado', 'PENDIENTE')->first();
                if ($estadoPendiente) {
                    $vacacion->estado_solicitud_id = $estadoPendiente->id;
                } else {
                    throw new BusinessException("El estado 'PENDIENTE' no fue encontrado en estados_solicitud. Por favor, asegúrate de que existe.", 500);
                }
            }
        });
    }

    // Scope para filtrar vacaciones por año de ciclo de servicio
    public function scopePorCiclo($query, $anio)
    {
        return $query->whereHas('cicloServicio', function ($q) use ($anio) {
            $q->where('anio', $anio);
        });
    }
}
