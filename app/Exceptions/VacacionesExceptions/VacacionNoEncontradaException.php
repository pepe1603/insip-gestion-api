<?php

namespace App\Exceptions\VacacionesExceptions;

use Exception;

class VacacionNoEncontradaException extends Exception
{
    public function __construct()
    {
        parent::__construct('La solicitud de vacaciones no fue encontrada.', 404);
    }
}
