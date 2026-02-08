<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Empleado extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'nombre',
        'ape_materno',
        'ape_paterno',
        'fecha_ingreso',
        'email',
        'telefono',
        'puesto',
        'departamento_id',
        'status',
        'tipo_contrato'
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'empleado_id');
    }


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
