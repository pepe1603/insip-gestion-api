<?php

namespace App\Exceptions\TiposAsistenciaExceptions;

use Exception;

class TipoAsistenciaExistenteException extends Exception
{
    protected $message = " (conflito) El tipo de Asistencia ya existe en el repositorio."; // Mensaje predeterminado

    public function __construct($message = "", $code = 409) // 404 Bad Request
    {
        if ($message != "") {
            $this->message = $message;
        }

        parent::__construct($this->message, $code);
    }
}
