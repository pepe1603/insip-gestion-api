<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//use App\Models\Asistencia; // Ensure the Asistencia model exists in this namespace
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoAsistencia extends Model
{
    use HasFactory;

    protected $table = 'tipo_asistencia';
    protected $fillable = [
        'nombre',
        'descripcion'
    ];
    public function asistencias() : HasMany
    {
        return $this->hasMany(Asistencia::class);
    }
}
