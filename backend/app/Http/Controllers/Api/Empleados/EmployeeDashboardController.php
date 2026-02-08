<?php

namespace App\Http\Controllers\Api\Empleados;

use App\Models\Empleado;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Carbon\Carbon; // Para manejo de fechas
use App\Models\EstadoSolicitud; // Para estados de solicitud
use App\Models\Asistencia; // Para obtener la última asistencia
use App\Exceptions\BusinessException; // Tu excepción de negocio
use App\Models\Vacaciones; // Para vacaciones pendientes y próximas
use App\Services\VacacionesService; // Para reutilizar la lógica de cálculo de vacaciones

class EmployeeDashboardController extends Controller
{
    protected $vacacionesService;

    public function __construct(VacacionesService $vacacionesService)
    {
        $this->middleware('auth:sanctum'); // Asegura que solo usuarios autenticados puedan acceder
        // Opcional: Podrías añadir un middleware para verificar si el usuario es el empleado_id que solicita,
        // o si es un administrador/supervisor que puede ver el dashboard de cualquier empleado.
        // Por ejemplo: $this->middleware('can:view-employee-dashboard,empleadoId');
        $this->vacacionesService = $vacacionesService;
    }

   /**
     * 1. Obtener Días de Vacaciones Disponibles para un Empleado.
     * GET /api/empleado-dashboard/{empleadoId}/vacaciones/disponibles
     *
     * @param int $empleadoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDiasVacacionesDisponibles(int $empleadoId): JsonResponse
    {
        try {
            // Paso 1: Verificar si el empleado existe.
            // Es buena práctica usar findOrFail o lanzar la excepción aquí si es un requisito fuerte.
            $empleado = Empleado::find($empleadoId);
            if (!$empleado) {
                // Si el empleado no se encuentra, retornamos un 404 directamente.
                return ApiResponse::error('Empleado no encontrado.', 404);
            }

            // Paso 2: Llamar al servicio de vacaciones.
            // Asumimos que $this->vacacionesService->getDisponibilidad($empleadoId)
            // devuelve un JsonResponse como lo hace tu método getDisponibilidad() principal.
            $serviceResponse = $this->vacacionesService->getDisponibilidad($empleadoId);

            // Paso 3: Validar la respuesta del servicio.
            // Debemos verificar el estado HTTP y la estructura del JSON retornado.
            if ($serviceResponse instanceof JsonResponse && $serviceResponse->getStatusCode() === 200) {
                // Acceder al contenido original del JsonResponse
                $originalContent = $serviceResponse->original;

                // Verificar si 'data' existe y si 'disponible' existe dentro de 'data'
                $diasDisponibles = $originalContent['data']['disponible'] ?? 0;

                return ApiResponse::success([
                    'empleado_id'      => $empleadoId,
                    'dias_disponibles' => (int) $diasDisponibles // Aseguramos que sea un entero
                ], 'Días de vacaciones disponibles obtenidos exitosamente.');

            } elseif ($serviceResponse instanceof JsonResponse && $serviceResponse->getStatusCode() !== 200) {
                // Si el servicio devolvió un error (ej. 400, 500)
                $errorMessage = $serviceResponse->original['data']['error'] ?? 'Error desconocido del servicio de disponibilidad.';
                $statusCode = $serviceResponse->getStatusCode();
                return ApiResponse::error($errorMessage, $statusCode);
            } else {
                // Si la respuesta del servicio no es un JsonResponse válido
                //\Log::error("El servicio VacacionesService::getDisponibilidad no devolvió un JsonResponse válido para empleado {$empleadoId}.");
                return ApiResponse::error('Error inesperado del servicio de disponibilidad.');
            }

        } catch (BusinessException $e) {
            // Captura las excepciones de negocio que tú mismo defines.
            return ApiResponse::error($e->getMessage());
        } catch (Throwable $e) { // Captura cualquier otra excepción (ej. si el servicio falla internamente de forma inesperada)
           // \Log::error("Error general en getDiasVacacionesDisponibles para empleado {$empleadoId}: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine());
            return ApiResponse::error('Error interno del servidor al obtener días de vacaciones disponibles.');
        }
    }

    /**
     * 2. Obtener la Última Asistencia del Empleado.
     * GET /api/empleado-dashboard/{empleadoId}/asistencias/ultima
     *
     * @param int $empleadoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUltimaAsistencia(int $empleadoId)
    {
        try {
            $empleado = Empleado::find($empleadoId);

            if (!$empleado) {
                throw new BusinessException('Empleado no encontrado.', 404);
            }

            $ultimaAsistencia = Asistencia::where('empleado_id', $empleadoId)
                                          ->orderByDesc('fecha')
                                          ->orderByDesc('created_at') // Para desempates en la misma fecha
                                          ->first();

            $data = null;
            if ($ultimaAsistencia) {
                $tipo = null;
                if ($ultimaAsistencia->hora_salida) {
                    $tipo = 'salida';
                } elseif ($ultimaAsistencia->hora_entrada) {
                    $tipo = 'entrada';
                }

                $data = [
                    'id' => $ultimaAsistencia->id,
                    'empleado_id' => $ultimaAsistencia->empleado_id,
                    'fecha' => Carbon::parse($ultimaAsistencia->fecha)->toDateString(),
                    'hora_entrada' => $ultimaAsistencia->hora_entrada,
                    'hora_salida' => $ultimaAsistencia->hora_salida,
                    'tipo' => $tipo,
                    'created_at' => Carbon::parse($ultimaAsistencia->created_at)->toISOString(),
                ];
            }

            return ApiResponse::success($data, 'Última asistencia obtenida exitosamente.');

        } catch (BusinessException $e) {
            return ApiResponse::send($e->getCode(), $e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener la última asistencia: ' . $e->getMessage());
        }
    }

    /**
     * 3. Obtener la Antigüedad del Empleado.
     * GET /api/empleado-dashboard/{empleadoId}/antiguedad
     *
     * @param int $empleadoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAntiguedad(int $empleadoId)
    {
        try {
            $empleado = Empleado::find($empleadoId);

            if (!$empleado) {
                throw new BusinessException('Empleado no encontrado.', 404);
            }

            $fechaIngreso = Carbon::parse($empleado->fecha_ingreso);
            $hoy = Carbon::now();
            $diff = $fechaIngreso->diff($hoy);

            return ApiResponse::success([
                'fecha_ingreso' => $fechaIngreso->toDateString(),
                'antiguedad_formato_humano' => $diff->format('%y años, %m meses y %d días'),
                'antiguedad_anos' => $diff->y,
                'antiguedad_meses' => $diff->m,
                'antiguedad_dias' => $diff->d
            ], 'Antigüedad del empleado obtenida exitosamente.');

        } catch (BusinessException $e) {
            return ApiResponse::send($e->getCode(), $e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener la antigüedad del empleado: ' . $e->getMessage());
        }
    }

    /**
     * 4. Obtener la Próxima Vacación Aprobada del Empleado.
     * GET /api/empleado-dashboard/{empleadoId}/vacaciones/proxima-aprobada
     *
     * @param int $empleadoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProximaVacacionAprobada(int $empleadoId)
    {
        try {
            $empleado = Empleado::find($empleadoId);

            if (!$empleado) {
                throw new BusinessException('Empleado no encontrado.', 404);
            }

            // Encuentra la próxima vacación aprobada para este empleado
            $proximaVacacion = Vacaciones::where('empleado_id', $empleadoId)
                                         ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
                                         ->where('fecha_inicio', '>=', now()->toDateString())
                                         ->orderBy('fecha_inicio', 'asc')
                                         ->first();

            $data = null;
            if ($proximaVacacion) {
                $data = [
                    'id' => $proximaVacacion->id,
                    'empleado_id' => $proximaVacacion->empleado_id,
                    'fecha_inicio' => Carbon::parse($proximaVacacion->fecha_inicio)->toDateString(),
                    'fecha_fin' => Carbon::parse($proximaVacacion->fecha_fin)->toDateString(),
                    'dias_solicitados' => $proximaVacacion->dias_vacaciones_solicitados,
                    'estado' => $proximaVacacion->estadoSolicitud->estado, // Asume relación con EstadoSolicitud
                    'created_at' => Carbon::parse($proximaVacacion->created_at)->toISOString(),
                ];
            }

            return ApiResponse::success($data, 'Próxima vacación aprobada obtenida exitosamente.');

        } catch (BusinessException $e) {
            return ApiResponse::send($e->getCode(), $e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener la próxima vacación aprobada: ' . $e->getMessage());
        }
    }

    /**
     * 5. Obtener Solicitudes Pendientes del Empleado.
     * GET /api/empleado-dashboard/{empleadoId}/solicitudes/pendientes
     *
     * @param int $empleadoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSolicitudesPendientes(int $empleadoId)
    {
        try {
            $empleado = Empleado::find($empleadoId);

            if (!$empleado) {
                throw new BusinessException('Empleado no encontrado.', 404);
            }

            // Asumiendo que todas las "solicitudes" (vacaciones, permisos, etc.)
            // se manejan a través del modelo `Vacaciones` con un `estado_solicitud_id`.
            // Si tienes un modelo `Solicitud` más genérico que englobe varios tipos (vacaciones, permisos),
            // la lógica deberá ajustarse para consultar ese modelo.
            // Por ahora, asumiremos que las "solicitudes" son principalmente `Vacaciones`
            // o que `Vacaciones` es el tipo de solicitud principal para este dashboard.
            // Si necesitas otros tipos de solicitudes (ej. permisos), deberás adaptar esto.

            $estadoPendiente = EstadoSolicitud::where('estado', 'PENDIENTE')->first();

            if (!$estadoPendiente) {
                throw new BusinessException('Estado "PENDIENTE" no encontrado. Verifique la tabla de estados de solicitud.', 500);
            }

            $solicitudesPendientes = Vacaciones::where('empleado_id', $empleadoId)
                                               ->where('estado_solicitud_id', $estadoPendiente->id)
                                               ->get();

            // Formatea la respuesta para que coincida con el ejemplo (ajusta según tus campos reales)
            $formattedSolicitudes = $solicitudesPendientes->map(function ($solicitud) {
                return [
                    'id' => $solicitud->id,
                    'empleado_id' => $solicitud->empleado_id,
                    'tipo_solicitud' => 'vacacion', // Asumimos que es vacación, ajustar si hay otros tipos
                    'fecha_solicitud' => Carbon::parse($solicitud->created_at)->toDateString(),
                    'fecha_inicio' => Carbon::parse($solicitud->fecha_inicio)->toDateString(),
                    'fecha_fin' => Carbon::parse($solicitud->fecha_fin)->toDateString(),
                    'estado' => $solicitud->estadoSolicitud->estado,
                    'comentarios' => $solicitud->motivo, // O el campo que uses para comentarios/razón
                ];
            })->toArray();

            return response()->json([
                'data' => $formattedSolicitudes, 'message'=> 'Solicitudes pendientes obtenidas exitosamente.'
            ]);

        } catch (BusinessException $e) {
            return ApiResponse::send($e->getCode(), $e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener solicitudes pendientes: ' . $e->getMessage());
        }
    }
}
