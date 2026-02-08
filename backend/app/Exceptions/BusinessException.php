<?php
namespace App\Exceptions;

use Exception;
class BusinessException extends Exception
{
    protected $message = "Error en la logica de negocio";

    public function __construct($message, $code = 422)
    {
        if($message != ""){
            $this->message = $message;
        }

        parent::__construct($this->message, $code);
    }
}
