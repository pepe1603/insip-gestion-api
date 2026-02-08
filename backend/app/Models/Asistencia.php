<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{

    use HasFactory;
    protected $fillable = [
        'empleado_id',
        'tipo_asistencia_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'observaciones'
    ];
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
    public function tipoAsistencia()
    {
        return $this->belongsTo(TipoAsistencia::class);
    }
    public function getEmpleadoFullName()
    {
        return $this->empleado->getFullName();
    }
    public function getTipoAsistenciaNombre()
    {
        return $this->tipoAsistencia->nombre;
    }
    public function getTipoAsistenciaDescripcion()
    {
        return $this->tipoAsistencia->descripcion;
    }
    public function getFechaFormatted()
    {
        return \Carbon\Carbon::parse($this->fecha)->format('d/m/Y');
    }
    public function getHoraEntradaFormatted()
    {
        return \Carbon\Carbon::parse($this->hora_entrada)->format('H:i');
    }
    public function getHoraSalidaFormatted()
    {
        return \Carbon\Carbon::parse($this->hora_salida)->format('H:i');
    }
    public function getObservacionesFormatted()
    {
        return $this->observaciones ? $this->observaciones : 'Sin observaciones';
    }
    public function getStatus()
    {
        return $this->hora_salida ? 'Asistido' : 'No asistido';
    }
}
