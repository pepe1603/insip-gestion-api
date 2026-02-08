<?php

namespace App\Exceptions\AsistenciasExceptions;

use Exception;

class AsistenciaNoEncontradaException extends Exception
{
    protected $message = "Asistencia no encontrada.";
    protected $code = 404; // Not Found
}
