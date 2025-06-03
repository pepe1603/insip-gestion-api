<?php

namespace App\Exceptions\EmpleadosExceptions;

use Exception;

class EmpleadoExistenteException extends Exception
{
    public function __construct($message = "(conflict) El empleado ya existe.", $code = 409)
    {
        parent::__construct($message, $code);
    }

}
