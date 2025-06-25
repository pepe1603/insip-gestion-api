<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Http\Request; // ¡CORREGIDO! De ilimuntae a Illuminate
use App\Helpers\ApiResponse;
use App\Exceptions\BusinessException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\EmpleadosExceptions\EmpleadoNoActivoException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Exceptions\EmpleadosExceptions\EmpleadoExistenteException;
use Symfony\Component\HttpFoundation\Response; // Asegúrate de que esta importación esté presente


use App\Exceptions\EmpleadosExceptions\EmpleadoNoEncontradoException;
use App\Exceptions\AsistenciasExceptions\AsistenciaExistenteException;
use App\Exceptions\VacacionesExceptions\VacacionNoEncontradaException;
use App\Exceptions\VacacionesExceptions\VacacionYaSolicitadaException;
use App\Exceptions\AsistenciasExceptions\AsistenciaHoraInvalidaException;
use App\Exceptions\AsistenciasExceptions\AsistenciaNoEncontradaException;
use App\Exceptions\VacacionesExceptions\VacacionesInsuficientesException;
use App\Exceptions\DepartamentosExceptions\DepartamentoExistenteException;
use App\Exceptions\DepartamentosExceptions\DepartamentoNoEncontradoException;




use App\Exceptions\TiposAsistenciaExceptions\TipoAsistenciaexistenteException;
use App\Exceptions\EstadosSolicitudExceptions\EstadoSolicitudExistenteException;
use App\Exceptions\TiposAsistenciaExceptions\TipoAsistenciaNoEncontradaException;
use App\Exceptions\EstadosSolicitudExceptions\EstadoSolicitudNoEncontradoException;
use App\Exceptions\VacacionesOficialesExceptions\VacacionesOficialesExistenteException;
use App\Exceptions\VacacionesOficialesExceptions\VacacionesOficialesNoEncontradasException;

class Handler extends ExceptionHandler
{

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // --- ¡Consolidado y corregido para AuthenticationException! ---
        $this->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                // Devolver siempre 401 Unauthorized para solicitudes API no autenticadas
                return response()->json(['message' => 'No autorizado. Se requiere autenticación.'], Response::HTTP_UNAUTHORIZED);
            }
            // Si no espera JSON, deja que el flujo normal de Laravel (o el método unauthenticated
            // en el middleware Authenticate) se encargue de la redirección si aplica.
        });

        // renderable para 404, etc..
        $this->renderable(function (NotFoundHttpException $e, $request){
            if ($request->is('api/*') || $request->expectsJson()){
                return response()->json([
                    'message' => 'Recurso no encontrado o endpoint no existe.', // Mensaje más claro
                ], 404);
            }
        });

        //renderable 422 de datos inválidos o de validaciones
        $this->renderable(function (ValidationException $e, $request){
            if ($request->is('api/*') || $request->expectsJson()){
                // El código de estado para validación fallida es 422 Unprocessable Entity
                return response()->json([
                    'message' => 'Los datos proporcionados no son válidos.', // Mensaje más claro
                    'errors' => $e->errors(),
                ], 422); // ¡CORREGIDO! De 404 a 422
            }
        });

        // Agregamos un renderable para ModelNotFoundException aquí para unificar el manejo
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Registro no encontrado.'], 404);
            }
        });

        // Puedes agregar más renderables específicos para tus BusinessExceptions aquí si quieres
        // un manejo más granular que el que ya tienes en el método `render`.
        // Por ejemplo:
        // $this->renderable(function (EmpleadoNoEncontradoException $e, $request) {
        //     if ($request->expectsJson()) {
        //         return ApiResponse::error($e->getMessage(), $e->getCode());
        //     }
        // });
    }

    /**
     * Convert an authentication exception into a response.
     * Este método NO debe estar en Handler.php. Pertenece a Authenticate.php
     * Si lo dejas aquí, puedes tener comportamientos inesperados o redundantes.
     * LO ELIMINARÉ EN LA VERSIÓN CORREGIDA FINAL, PERO LO MENCIONO AQUÍ.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    // protected function unauthenticated($request, AuthenticationException $exception)
    // {
    //     if ($request->expectsJson() || $request->is('api/*')) {
    //         return response()->json(['message' => 'Unauthenticated'], 401);
    //     }

    //     return redirect()->guest($exception->redirectTo() ?? route('welcome'));
    // }


    // El método render es donde tus ApiResponse personalizados se aplican.
    // Asegúrate de que las excepciones más específicas se manejen antes que las más generales.
    public function render($request, Throwable $exception)
    {
        // Las excepciones manejadas por renderable() en el método register() tienen prioridad.
        // Solo llegaremos aquí para las excepciones que NO fueron manejadas por renderable().

        // Excepciónes de negocio (estas deberían tener códigos de estado HTTP apropiados en sus constructores)
        if ($exception instanceof BusinessException) {
            return ApiResponse::error($exception->getMessage(), $exception->getCode());
        }

        // Tus excepciones personalizadas
        if ($exception instanceof TipoAsistenciaNoEncontradaException ||
            $exception instanceof VacacionesOficialesNoEncontradasException ||
            $exception instanceof EstadoSolicitudNoEncontradoException ||
            $exception instanceof DepartamentoNoEncontradoException ||
            $exception instanceof AsistenciaNoEncontradaException ||
            $exception instanceof VacacionNoEncontradaException ||
            $exception instanceof EmpleadoNoEncontradoException) {
            return ApiResponse::error($exception->getMessage(), $exception->getCode()); // Espera 404
        }

        if ($exception instanceof TipoAsistenciaexistenteException ||
            $exception instanceof VacacionesOficialesExistenteException ||
            $exception instanceof EstadoSolicitudExistenteException ||
            $exception instanceof DepartamentoExistenteException ||
            $exception instanceof AsistenciaExistenteException ||
            $exception instanceof VacacionYaSolicitadaException ||
            $exception instanceof EmpleadoExistenteException ||
            $exception instanceof AsistenciaHoraInvalidaException ||
            $exception instanceof VacacionesInsuficientesException ||
            $exception instanceof EmpleadoNoActivoException) {
            return ApiResponse::error($exception->getMessage(), $exception->getCode()); // Espera 400 o 403
        }

        // Manejo de ValidationException: Ya lo tienes en renderable, pero si por alguna razón llega aquí...
        if ($exception instanceof ValidationException) {
            return response()->json(['errors' => $exception->validator->errors()], 422);
        }

        // Manejo de ModelNotFoundException: Ya lo tienes en renderable, pero si por alguna razón llega aquí...
        if ($exception instanceof ModelNotFoundException) {
            return ApiResponse::error('Recurso no encontrado.', 404);
        }

        // Para cualquier otra excepción no capturada por los renderables o tus excepciones específicas
        if ($request->expectsJson()) {
            // Loguear la excepción para depuración
            // \Log::error($exception);
            return ApiResponse::serverError('Ocurrió un error inesperado en el servidor.');
        }

        // Comportamiento por defecto para otras excepciones si no es una solicitud JSON
        return parent::render($request, $exception);
    }
}
