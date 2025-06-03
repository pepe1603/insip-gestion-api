<?php

namespace App\Exceptions\EmpleadosExceptions;

use Exception;

class EmpleadoNoActivoException extends Exception
{
    protected $message = "El empleado no está activo."; // Mensaje predeterminado

    public function __construct($message = "", $code = 403) // 403 Forbidden
    {
        if ($message != "") {
            $this->message = $message;
        }

        parent::__construct($this->message, $code);
    }
}
