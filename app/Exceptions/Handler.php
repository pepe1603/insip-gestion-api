<?php

namespace App\Exceptions;

use App\Exceptions\AsistenciasExceptions\AsistenciaExistenteException;
use App\Exceptions\AsistenciasExceptions\AsistenciaHoraInvalidaException;
use App\Exceptions\AsistenciasExceptions\AsistenciaNoEncontradaException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Exceptions\BusinessException;
use App\Exceptions\DepartamentosExceptions\DepartamentoExistenteException;
use App\Exceptions\DepartamentosExceptions\DepartamentoNoEncontradoException;
use App\Exceptions\EmpleadosExceptions\EmpleadoExistenteException;
use App\Exceptions\EmpleadosExceptions\EmpleadoNoActivoException;
use App\Exceptions\EmpleadosExceptions\EmpleadoNoEncontradoException;


use App\Exceptions\EstadosSolicitudExceptions\EstadoSolicitudExistenteException;
use App\Exceptions\EstadosSolicitudExceptions\EstadoSolicitudNoEncontradoException;
use App\Exceptions\TiposAsistenciaExceptions\TipoAsistenciaexistenteException;
use App\Exceptions\TiposAsistenciaExceptions\TipoAsistenciaNoEncontradaException;
use App\Exceptions\VacacionesExceptions\VacacionesInsuficientesException;
use App\Exceptions\VacacionesExceptions\VacacionNoEncontradaException;
use App\Exceptions\VacacionesExceptions\VacacionYaSolicitadaException;
use App\Exceptions\VacacionesOficialesExceptions\VacacionesOficialesExistenteException;
use App\Exceptions\VacacionesOficialesExceptions\VacacionesOficialesNoEncontradasException;




use App\Helpers\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{


    public function render($request, Throwable $exception)
    {
        $response = null;

        // Excepciónes de negocio
        if ($exception instanceof BusinessException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode()); // Usamos el código de estado de la excepción
        }

        // Excepciónes de Tipo asistencia
         elseif ($exception instanceof TipoAsistenciaNoEncontradaException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode()); // 404 Not Found
        } elseif ($exception instanceof TipoAsistenciaexistenteException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode()); // 400 Bad Request
        }

        // Excepciónes de Vacaciones Oficiales
        elseif ($exception instanceof VacacionesOficialesNoEncontradasException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode());
        } elseif ($exception instanceof VacacionesOficialesExistenteException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode());
        }

        // Excepciónes de Estados de Solicitud
        elseif ($exception instanceof EstadoSolicitudNoEncontradoException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode());
        } elseif ($exception instanceof EstadoSolicitudExistenteException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode());
        }

        // Excepciónes de departamento
        elseif ($exception instanceof DepartamentoNoEncontradoException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode());
        } elseif ($exception instanceof DepartamentoExistenteException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode());
        }

        // Excepciónes de asistencia
        elseif ($exception instanceof AsistenciaNoEncontradaException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode());
        } elseif ($exception instanceof AsistenciaExistenteException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode());
        } elseif ($exception instanceof AsistenciaHoraInvalidaException ) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode());
        }

        // Excepciónes de vacaciones
        elseif ($exception instanceof VacacionNoEncontradaException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode());
        } elseif ($exception instanceof VacacionYaSolicitadaException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode());
        } elseif ($exception instanceof VacacionesInsuficientesException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode());
        }

        // Excepciónes de empleado
        elseif ($exception instanceof EmpleadoNoActivoException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode()); // 403 Bad Request
        } elseif ($exception instanceof EmpleadoNoEncontradoException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode()); // 404 Not Found
        } elseif ($exception instanceof EmpleadoExistenteException) {
            $response = ApiResponse::error($exception->getMessage(), $exception->getCode()); // 400 Bad Request
        }

        // Manejo de ModelNotFoundException
        elseif ($exception instanceof ModelNotFoundException) {
            $response = ApiResponse::error('Recurso no encontrado.', 404);
        }
        // Manejo de ValidationException
        elseif ($exception instanceof ValidationException) {
            return response()->json(['errors' => $exception->validator->errors()], 422);
        }
        elseif ($exception instanceof \Exception) {
            $response = ApiResponse::serverError($exception->getMessage() ?: 'Ocurrió un error inesperado en el servidor.');
        }

        else {


            // Para errores no controlados
            $response = ApiResponse::serverError('Ocurrió un error inesperado');
        }

        return $response;
    }
}
