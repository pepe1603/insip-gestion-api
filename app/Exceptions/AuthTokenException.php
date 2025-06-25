<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class AuthTokenException extends Exception
{
    protected $statusCode;
    protected $reason; // Para almacenar la razón del fallo del token

    public function __construct($message = "", $reason = "unknown", int $statusCode = Response::HTTP_UNAUTHORIZED, Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->reason = $reason;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
