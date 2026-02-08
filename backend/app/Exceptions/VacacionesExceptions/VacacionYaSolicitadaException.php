<?php

namespace App\Exceptions\VacacionesExceptions;

use Exception;

class VacacionYaSolicitadaException extends Exception
{
    public function __construct()
    {
        parent::__construct('Ya existe una solicitud de vacaciones en el mismo rango de fechas.', 409);
    }
}
