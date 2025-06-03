<?php

namespace App\Exceptions\DepartamentosExceptions;

use Exception;

class DepartamentoExistenteException extends Exception
{
    protected $message = "(conflicto) El departamento ya existe.";
    protected $code = 409; // Bad Request
}
