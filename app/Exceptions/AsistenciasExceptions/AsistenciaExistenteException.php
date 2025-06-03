<?php

namespace App\Exceptions\AsistenciasExceptions;

use Exception;

class AsistenciaExistenteException extends Exception
{
    protected $message = "(conflicto) La asistencia ya existe.";
    protected $code = 409; // Bad Request
}
