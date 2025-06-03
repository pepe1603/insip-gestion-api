<?php

namespace App\Exceptions\VacacionesOficialesExceptions;

use Exception;

class VacacionesOficialesNoEncontradasException extends Exception
{
    protected $message = "Vacaciones oficiales no encontradas.";
    protected $code = 404; // Not Found
}
