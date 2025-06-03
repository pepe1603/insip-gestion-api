<?php

namespace App\Exceptions\VacacionesOficialesExceptions;

use Exception;

class VacacionesOficialesExistenteException extends Exception
{
    protected $message = "(conflicto) La vacacion oficial ya existen";
    protected $code = 409; // Bad Request
}
