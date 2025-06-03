<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacaciones extends Model
{
    use HasFactory;

    protected $table = 'vacaciones';

    protected $fillable = [
        'empleado_id',
        'ciclo_servicio_id', // Ahora guardamos el ciclo correcto
        'dias_vacaciones_totales',
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

    // Boot para asignar ciclo de servicio automáticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vacacion) {
            if (!$vacacion->empleado_id) {
                throw new \Exception("El empleado no existe para asignar el ciclo de servicio.");
            }

            // Busca o crea el ciclo de servicio correspondiente al año actual
            $cicloServicio = CicloServicio::firstOrCreate([
                'empleado_id' => $vacacion->empleado_id,
                'anio' => now()->year,
            ]);

            $vacacion->ciclo_servicio_id = $cicloServicio->id;
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
