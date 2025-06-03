<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'ape_materno',
        'ape_paterno',
        'fecha_ingreso',
        'puesto',
        'departamento_id',
        'status',
        'tipo_contrato'
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }
    public function vacaciones() : HasMany
    {
        return $this->hasMany(Vacaciones::class);
    }


    public function getFullName()
    {
        return "{$this->nombre} {$this->ape_paterno} {$this->ape_materno}";
    }
}
