<?php

namespace App\Exceptions\TiposAsistenciaExceptions;

use Exception;

class TipoAsistenciaNoEncontradaException extends Exception
{
    protected $message = "El tipo de asistencia no se encuentra en el repositorio."; // Mensaje predeterminado

    public function __construct($message = "", $code = 404) // 404 Bad Requeest
    {
        if ($message != "") {
            $this->message = $message;
        }

        parent::__construct($this->message, $code);
    }
}
