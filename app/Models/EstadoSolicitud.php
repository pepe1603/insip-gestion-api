<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoSolicitud extends Model
{
    use HasFactory;
    protected $table = 'estados_solicitud';
    protected $fillable = [
        'estado',
        'descripcion'
    ];

    public function vacaciones() :HasMany
    {
        return $this->hasMany(Vacaciones::class);
    }

}
