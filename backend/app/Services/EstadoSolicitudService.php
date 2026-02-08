<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Empleado;
use App\Helpers\ApiResponse;
use App\Models\EstadoSolicitud;
use Illuminate\Support\Facades\Log;
use App\Exceptions\EstadosSolicitudExceptions\EstadoSolicitudExistenteException;
use App\Exceptions\EstadosSolicitudExceptions\EstadoSolicitudNoEncontradoException;

class EstadoSolicitudService
{
    // Obtener todos los estados de solicitud
    public function all()
    {
        $estados = EstadoSolicitud::all();

        if ($estados->isEmpty()) {
            return ApiResponse::error('No se encontraron estados de solicitud.', 404);
        }

        return ApiResponse::success($estados);
    }

    // Obtener un estado de solicitud por ID
    public function find($id)
    {
        $estado = EstadoSolicitud::find($id);
        if (!$estado) {
            throw new EstadoSolicitudNoEncontradoException();
        }

        return ApiResponse::success($estado);
    }

    // Crear un nuevo estado de solicitud
    public function create(array $data)
    {
        // Verificar si el estado de solicitud ya existe (ejemplo: por nombre único)
        if (EstadoSolicitud::where('estado', $data['estado'])->exists()) {
            throw new EstadoSolicitudExistenteException();
        }

        $estado = EstadoSolicitud::create($data);
        return ApiResponse::success($estado);
    }

    // Actualizar un estado de solicitud por ID
    public function update($id, array $data)
    {
        $estado = EstadoSolicitud::find($id);
        if (!$estado) {
            throw new EstadoSolicitudNoEncontradoException();
        }

        $estado->update($data);
        return ApiResponse::success($estado);
    }

    // Actualizar parcialmente un estado de solicitud por ID
    public function updatePartial($id, array $data)
    {
        $estado = EstadoSolicitud::find($id);
        if (!$estado) {
            throw new EstadoSolicitudNoEncontradoException();
        }

        $estado->update($data);
        return ApiResponse::success($estado);
    }

    // Eliminar un estado de solicitud
    public function delete($id)
    {
        $estado = EstadoSolicitud::find($id);
        if (!$estado) {
            throw new EstadoSolicitudNoEncontradoException();
        }

        $estado->delete();
        return ApiResponse::send(204, ['message' => 'Estado de solicitud eliminado correctamente.']);
    }


    // ## Nuevos Métodos para Dashboard y Antigüedad


 /**
     * Obtiene el conteo de empleados por su estado (ACTIVO/INACTIVO).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatusCounts()
    {
        try {
            $activeCount = Empleado::where('status', 'ACTIVO')->count();
            $inactiveCount = Empleado::where('status', 'INACTIVO')->count();

            return response()->json([
                'message' => 'Conteo de empleados por estado obtenido exitosamente.',
                'data' => [
                    'activo' => $activeCount,
                    'inactivo' => $inactiveCount,
                    'total' => $activeCount + $inactiveCount,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error al obtener conteo de empleados por estado: " . $e->getMessage());
            return response()->json([
                'message' => 'Hubo un error al obtener el conteo de empleados por estado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los empleados que han ingresado recientemente (ej. en los últimos 30 días).
     * Permite un parámetro 'days' para definir el rango de días.
     *
     * @param int $days Número de días hacia atrás para considerar "reciente".
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentlyHired(int $days = 30)
    {
        try {
            $cutOffDate = Carbon::now()->subDays($days);

            $recentHires = Empleado::where('fecha_ingreso', '>=', $cutOffDate)
                                    ->with('departamento')
                                    ->orderBy('fecha_ingreso', 'desc')
                                    ->get();

            return response()->json([
                'message' => "Empleados contratados en los últimos {$days} días obtenidos exitosamente.",
                'data' => $recentHires
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error al obtener empleados recién ingresados: " . $e->getMessage());
            return response()->json([
                'message' => 'Hubo un error al obtener los empleados recién ingresados.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcula y devuelve la antigüedad de un empleado específico.
     *
     * @param int $empleadoId El ID del empleado.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAntiguedad(int $empleadoId)
    {
        try {
            $empleado = Empleado::select('id', 'nombre', 'ape_paterno', 'ape_materno', 'fecha_ingreso')
                                ->find($empleadoId);

            if (!$empleado) {
                return response()->json([
                    'message' => 'Empleado no encontrado.'
                ], 404);
            }

            $fechaIngreso = Carbon::parse($empleado->fecha_ingreso);
            $now = Carbon::now();

            $antiguedad = $fechaIngreso->diff($now);

            return response()->json([
                'message' => 'Antigüedad del empleado obtenida exitosamente.',
                'data' => [
                    'empleado_id' => $empleado->id,
                    'nombre_completo' => $empleado->getFullName(),
                    'fecha_ingreso' => $empleado->fecha_ingreso,
                    'antiguedad' => [
                        'años' => $antiguedad->y,
                        'meses' => $antiguedad->m,
                        'dias' => $antiguedad->d,
                        'total_dias' => $fechaIngreso->diffInDays($now),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error al obtener la antigüedad del empleado: " . $e->getMessage());
            return response()->json([
                'message' => 'Hubo un error al obtener la antigüedad del empleado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
