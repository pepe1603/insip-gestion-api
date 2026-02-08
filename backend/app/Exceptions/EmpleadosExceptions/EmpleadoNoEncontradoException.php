<?php

namespace App\Exceptions\EmpleadosExceptions;

use Exception;

class EmpleadoNoEncontradoException extends Exception
{
    protected $message = "Empleado no encontrado."; // Mensaje predeterminado

    public function __construct($message = "", $code = 404) // 404 Not Found
    {
        // Si se proporciona un mensaje personalizado, lo usamos
        // de lo contrario, usamos el mensaje predeterminado
        if ($message != "") {
            $this->message = $message;
        }

        parent::__construct($this->message, $code);
    }
}
