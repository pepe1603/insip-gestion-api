<?php

namespace App\Exceptions\AsistenciasExceptions;

use Exception;

class AsistenciaHoraInvalidaException extends Exception
{
    protected $message = "La hora de salida es menor a la hora de entrada.";
    protected $code = 400; // Bad Request


}
