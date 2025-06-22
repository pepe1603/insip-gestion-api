<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Exceptions\BusinessException;

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vacacion) {
            // Asegurarse de que el empleado_id esté presente antes de continuar
            if (empty($vacacion->empleado_id)) {
                throw new BusinessException("El 'empleado_id' es necesario para crear un registro de vacaciones.", 400);
            }

            // Asegurar la asignación del ciclo de servicio actual
            // Esto es un fallback. Idealmente, el servicio ya lo provee.
            if (empty($vacacion->ciclo_servicio_id)) {
                $cicloServicioActual = \App\Models\CicloServicio::firstOrCreate([
                    'empleado_id' => $vacacion->empleado_id,
                    'anio' => Carbon::now()->year,
                ]);
                $vacacion->ciclo_servicio_id = $cicloServicioActual->id;
            }

            // Establecer valores por defecto solo si NO han sido provistos por el servicio.
            // Esto es para asegurar que el registro tenga valores válidos si el servicio
            // accidentalmente no los define, sin imponer lógica de negocio.
            $vacacion->dias_vacaciones_totales        = $vacacion->dias_vacaciones_totales ?? 0;
            $vacacion->dias_vacaciones_arrastrados    = $vacacion->dias_vacaciones_arrastrados ?? 0;
            $vacacion->dias_vacaciones_disfrutados    = $vacacion->dias_vacaciones_disfrutados ?? 0;
            $vacacion->dias_vacaciones_solicitados    = $vacacion->dias_vacaciones_solicitados ?? 0;
            $vacacion->dias_vacaciones_disponibles    = $vacacion->dias_vacaciones_disponibles ?? 0;
            $vacacion->fecha_solicitud                = $vacacion->fecha_solicitud ?? Carbon::now();

            // El estado_solicitud_id DEBE ser provisto por el servicio o el Observer.
            // Si no lo es, es un error de programación en la lógica de creación.
            if (empty($vacacion->estado_solicitud_id)) {
                throw new BusinessException("El 'estado_solicitud_id' debe ser proporcionado al crear un registro de vacaciones.", 500);
            }
        });

        static::updating(function ($vacacion) {
            // Ejemplo: Si el estado cambia a APROBADO, registrar fecha de aprobación
            if ($vacacion->isDirty('estado_solicitud_id')) {
                $nuevoEstado = EstadoSolicitud::find($vacacion->estado_solicitud_id);
                if ($nuevoEstado && strtoupper($nuevoEstado->estado) === 'APROBADO') {
                    $vacacion->fecha_aprobacion = Carbon::now();
                }
            }
        });
    }

    public function scopePorCiclo($query, $anio)
    {
        return $query->whereHas('cicloServicio', function ($q) use ($anio) {
            $q->where('anio', $anio);
        });
    }
}
