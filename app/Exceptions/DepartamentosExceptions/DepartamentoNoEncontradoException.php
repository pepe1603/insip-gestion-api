<?php

namespace App\Exceptions\DepartamentosExceptions;

use Exception;

class DepartamentoNoEncontradoException extends Exception
{
    protected $message = "Departamento no encontrado.";
    protected $code = 404; // Not Found
}
