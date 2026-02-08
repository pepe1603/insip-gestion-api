<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacacionesOfficiales extends Model
{
    use HasFactory;
    protected $table = 'vacaciones_officiales';
    protected $fillable = [
        'tiempo_servicio',
        'dias_vacaciones',
    ];
}
