<?php

namespace App\Http\Controllers\Api\Vacaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vacaciones\StoreVacacionesRequest;
use App\Http\Requests\Vacaciones\UpdateVacacionesRequest;
use App\Services\VacacionesService;
use App\Exceptions\BusinessException;
use App\Exceptions\EmpleadoNoEncontradoException;
use App\Exceptions\EstadoSolicitudNoEncontradoException;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class VacacionesController extends Controller
{
    protected $vacacionesService;

    public function __construct(VacacionesService $vacacionesService)
    {
        $this->vacacionesService = $vacacionesService;
    }


    public function index()
    {
        return $this->vacacionesService->all();
    }

    public function store(StoreVacacionesRequest $request)
    {
        try {
            return $this->vacacionesService->registrarSolicitud($request->validated());

        } catch (BusinessException | EmpleadoNoEncontradoException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Endpoint para inicializar el registro de vacaciones para un empleado
     * con historial manual de días arrastrados.
     * Solo debe ser usado por administradores para configurar el primer registro anual de vacaciones.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function inicializarVacacionesHistoricas(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'dias_vacaciones_arrastrados' => 'nullable|integer|min:0', // Permite 0 o un número positivo
        ]);

        try {
            $vacacionInicial = $this->vacacionesService->inicializarVacacionesEmpleadoHistorico($request->all());
            return $vacacionInicial;
        } catch (BusinessException | EmpleadoNoEncontradoException | EstadoSolicitudNoEncontradoException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 400); // Proporciona un código por defecto si la excepción no lo tiene
        } catch (\Exception $e) {
            \Log::error("Error en inicializarVacacionesHistoricas: " . $e->getMessage() . " en " . $e->getFile() . " linea " . $e->getLine());
            return ApiResponse::error('Error interno al inicializar vacaciones históricas.', 500);
        }
    }


    public function show($id)
    {
        return $this->vacacionesService->find($id);
    }

    public function update(UpdateVacacionesRequest $request, $id)
    {
        return $this->vacacionesService->update($id, $request->validated());
    }

    public function destroy($id)
    {
        return $this->vacacionesService->delete($id);
    }

    public function aprobar($id)
    {
        return $this->vacacionesService->aprobarSolicitud($id);
    }

    public function rechazar($id)
    {
        return $this->vacacionesService->rechazarSolicitud($id);
    }

    public function cancelar($id)
    {
        return $this->vacacionesService->cancelarSolicitud($id);
    }

    public function porEmpleado($empleadoId)
    {
        return $this->vacacionesService->getByEmpleado($empleadoId);
    }

    public function porEstado($estadoId)
    {
        return $this->vacacionesService->getPorEstado($estadoId);
    }

    public function pendientes()
    {
        return $this->vacacionesService->getPendientes();
    }

    public function porPeriodo($desde, $hasta)
    {
        return  $this->vacacionesService->getPorPeriodo($desde, $hasta);
    }

    public function getDisponibilidad($empleadoId)
    {
        try {

            return $this->vacacionesService->getDisponibilidad($empleadoId);

        } catch (EmpleadoNoEncontradoException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener la disponibilidad de vacaciones: ' . $e->getMessage(), 500);
        }
    }

    //ruta par consultar-disponibilidad
    public function consultarDisponibilidad(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
        ]);

        try {
            return $this->vacacionesService->consultarDisponibilidad($request);
        } catch (EmpleadoNoEncontradoException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return ApiResponse::error('Error al consultar la disponibilidad: ' . $e->getMessage(), 500);
        }
    }

//------- Dashjbvoard metodss --------

    /**
     * Obtiene el resumen de solicitudes de vacaciones por estado para el dashboard.
     * GET /api/dashboard/vacaciones/resumen-estados/{anio}
     */
    public function getResumenEstadosVacaciones(Request $request, int $anio)
    {
        try {
            $data = $this->vacacionesService->getResumenVacacionesPorEstado($anio);
            return ApiResponse::success($data, 'Resumen de estados de vacaciones obtenido exitosamente.');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener el resumen de estados: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtiene las próximas vacaciones aprobadas para el dashboard.
     * GET /api/dashboard/vacaciones/proximas
     */
    public function getProximasVacaciones()
    {
        try {
            $data = $this->vacacionesService->getVacacionesProximas();
            return ApiResponse::success($data, 'Próximas vacaciones obtenidas exitosamente.');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener próximas vacaciones: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtiene el total de días de vacaciones aprobados por mes para el dashboard.
     * GET /api/dashboard/vacaciones/dias-por-mes/{anio}
     */
    public function getDiasVacacionesPorMes(Request $request, int $anio)
    {
        try {
            $data = $this->vacacionesService->getDiasVacacionesPorMesAcumulado($anio);
            return ApiResponse::success($data, 'Días de vacaciones por mes obtenidos exitosamente.');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener días de vacaciones por mes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtiene la disponibilidad general de vacaciones de todos los empleados para el dashboard.
     * GET /api/dashboard/vacaciones/disponibilidad-general
     */
    public function getDisponibilidadGeneralVacaciones()
    {
        try {
            $data = $this->vacacionesService->getDisponibilidadVacacionesGeneral();
            return ApiResponse::success($data, 'Disponibilidad general de vacaciones obtenida exitosamente.');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener disponibilidad general de vacaciones: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtiene los empleados con mayor antigüedad para el dashboard.
     * GET /api/dashboard/empleados/top-antiguos
     */
    public function getTopEmpleadosAntiguos()
    {
        try {
            $data = $this->vacacionesService->getTopEmpleadosMasAntiguos();
            return ApiResponse::success($data, 'Top empleados con mayor antigüedad obtenidos exitosamente.');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener top empleados antiguos: ' . $e->getMessage(), 500);
        }
    }

}
