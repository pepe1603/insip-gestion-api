<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CicloServicio extends Model
{
    protected $fillable = [
        'empleado_id',
         'anio',
                 // Asegúrate de añadir aquí 'dias_vacaciones_ganados' y 'dias_vacaciones_tomados'
        // si planeas agregarlos a la migración y usarlos en el futuro para saldos de vacaciones.
        // 'dias_vacaciones_ganados',
        // 'dias_vacaciones_tomados',
        ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function vacaciones()
    {
        return $this->hasMany(Vacaciones::class);
    }
}
