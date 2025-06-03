<?php

namespace App\Exceptions\EstadosSolicitudExceptions;

use Exception;

class EstadoSolicitudNoEncontradoException extends Exception
{
    protected $message = "Estado de solicitud no encontrado.";
    protected $code = 404; // Not Found
}
