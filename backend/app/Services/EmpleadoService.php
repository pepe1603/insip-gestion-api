<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Empleado;
use App\Helpers\ApiResponse;
use App\Models\Departamento;
use App\Exceptions\EmpleadosExceptions\EmpleadoNoActivoException;
use App\Exceptions\EmpleadosExceptions\EmpleadoExistenteException;
use App\Exceptions\EmpleadosExceptions\EmpleadoNoEncontradoException;

class EmpleadoService
{
    // Obtener todos los empleados paginados
    public function all()
    {
        //obtener lista de empelados con departamento
        //sin paginacion
        // $empleados = Empleado::with('departamento')->get();
        $empleados = Empleado::with('departamento')->get();

        if ($empleados->isEmpty()) {
            return ApiResponse::error('No se encontraron empleados.', 404);
        }

        return ApiResponse::success($empleados);
    }

    // Obtener un empleado por ID
    public function find($id)
    {
        $empleado = Empleado::with('departamento')->findOrFail($id);
        return ApiResponse::success($empleado);
    }

    // Crear un nuevo empleado
    public function create(array $data)
    {
        if (empty($data['email'])) {
            throw new BusinessException("El correo electrónico del empleado es fundamental y no puede estar vacío.", 422);
        }

        //evitar email duplicado
        if (Empleado::where('email', $data['email'])->exists()) {
            return ApiResponse::error('El correo electrónico ya está en uso por otro empleado.', 422);
        }

        // Validar duplicado por nombre completo y fecha ingreso
        if (Empleado::where('nombre', $data['nombre'])
            ->where('ape_paterno', $data['ape_paterno'])
            ->where('ape_materno', $data['ape_materno'])
            ->where('fecha_ingreso', $data['fecha_ingreso'])
            ->exists()) {
            throw new EmpleadoExistenteException("Ya existe un empleado con esos datos.");
        }

        // Validar que el departamento exista
        if (!Departamento::where('id', $data['departamento_id'])->exists()) {
            throw new EmpleadoNoEncontradoException("El departamento especificado no existe.");
        }

        //validar que el status sea activo o inactivo
        if (!in_array($data['status'], ['ACTIVO', 'INACTIVO'])) {
            return ApiResponse::error('Status inválido. Solo se permite ACTIVO o INACTIVO.', 422);
        }


        $empleado = Empleado::create($data);
        return ApiResponse::success($empleado->fresh());
    }

    // Actualizar un empleado por ID
    public function update($id, array $data)
    {
        $empleado = Empleado::findOrFail($id);

        if ($empleado->status !== 'ACTIVO') {
            throw new EmpleadoNoActivoException("El empleado no está activo.");
        }

        //validar emila duplicado
        if (Empleado::where('email', $data['email'])->where('id', '!=', $id)->exists()) {
            return ApiResponse::error('El correo electrónico ya está en uso por otro empleado.', 422);
        }

        // Validar duplicado si se están cambiando datos clave
        if (
            Empleado::where('nombre', $data['nombre'])
                ->where('ape_paterno', $data['ape_paterno'])
                ->where('ape_materno', $data['ape_materno'])
                ->where('fecha_ingreso', $data['fecha_ingreso'])
                ->where('id', '!=', $id)
                ->exists()
        ) {
            throw new EmpleadoExistenteException("Ya existe otro empleado con esos datos.");
        }

        $empleado->update($data);
        return ApiResponse::success($empleado->fresh());
    }

    // Actualización parcial
    public function updatePartial($id, array $data)
    {
        $empleado = Empleado::findOrFail($id);

        if ($empleado->status !== 'ACTIVO') {
            throw new EmpleadoNoActivoException("El empleado no está activo.");
        }

        $empleado->update($data);
        return ApiResponse::success($empleado->fresh());
    }

    // Eliminar un empleado
    public function delete($id)
    {
        $empleado = Empleado::findOrFail($id);

        if ($empleado->status !== 'ACTIVO') {
            throw new EmpleadoNoActivoException("Solo se pueden eliminar empleados activos.");
        }

        $empleado->delete();
        return ApiResponse::send(204, ['message' => 'Empleado eliminado correctamente.']);
    }

    // Obtener empleados por departamento
    public function getByDepartamento($departamentoId)
    {
        Departamento::findOrFail($departamentoId);

        $empleados = Empleado::where('departamento_id', $departamentoId);

        if ($empleados->isEmpty()) {
            return ApiResponse::send('No hay empleados en este departamento.', 404);
        }

        return ApiResponse::success($empleados);
    }

    // Obtener empleados activos
    public function getActivos()
    {
        $empleados = Empleado::where('status', 'ACTIVO');

        if ($empleados->isEmpty()) {
            return ApiResponse::send('No hay empleados activos.', 404);
        }

        return ApiResponse::success($empleados);
    }

    // Buscar empleados por nombre (búsqueda flexible)
    public function buscarPorNombre(string $nombre)
    {
        $empleados = Empleado::where('nombre', 'LIKE', "%$nombre%")
            ->orWhere('ape_paterno', 'LIKE', "%$nombre%")
            ->orWhere('ape_materno', 'LIKE', "%$nombre%");

        if ($empleados->isEmpty()) {
            return ApiResponse::send('No se encontraron empleados con ese nombre.', 404);
        }

        return ApiResponse::success($empleados);
    }

    // Cambiar el status de un empleado (ACTIVO <-> INACTIVO)
    public function cambiarStatus($id, string $nuevoStatus)
    {
        $empleado = Empleado::findOrFail($id);

        if (!in_array($nuevoStatus, ['ACTIVO', 'INACTIVO'])) {
            return ApiResponse::error('Status inválido. Solo se permite ACTIVO o INACTIVO.', 422);
        }

        if ($empleado->status === $nuevoStatus) {
            return ApiResponse::error("El empleado ya tiene el status '{$nuevoStatus}'.", 400);
        }

        $empleado->update(['status' => $nuevoStatus]);

        return ApiResponse::success([
            'message' => "Status actualizado correctamente a {$nuevoStatus}.",
            'empleado' => $empleado->fresh()
        ]);
    }

    public function getEmpleadosConDetalles()
    {
        $empleados = Empleado::with('departamento')->get();

        if ($empleados->isEmpty()) {
            return ApiResponse::error('No se encontraron empleados.', 404);
        }

        $datos = $empleados->map(function ($empleado) {
            return [
                'nombre_completo' => $empleado->nombre . ' ' . $empleado->ape_paterno . ' ' . $empleado->ape_materno,
                'email' => $empleado->email,
                'telefono' => $empleado->telefono,
                'fecha_ingreso' => $empleado->fecha_ingreso,
                'puesto' => $empleado->puesto,
                'departamento' => $empleado->departamento->nombre ?? 'Sin departamento',
                'status' => $empleado->status,
                'tipo_contrato' => $empleado->tipo_contrato,
            ];
        });

        return ApiResponse::success($datos);
    }


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
