<?php

namespace App\Exceptions\VacacionesExceptions;

use Exception;

class VacacionesInsuficientesException extends Exception
{
    public function __construct()
    {
        parent::__construct('No hay suficientes días de vacaciones disponibles para esta solicitud.', 422);
    }
}
