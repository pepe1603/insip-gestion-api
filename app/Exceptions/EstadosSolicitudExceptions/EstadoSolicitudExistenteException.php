<?php

namespace App\Exceptions\EstadosSolicitudExceptions;

use Exception;

class EstadoSolicitudExistenteException extends Exception
{
    protected $message = "(conflicto) El estado de solicitud ya existe.";
    protected $code = 409; // Bad Request
}
