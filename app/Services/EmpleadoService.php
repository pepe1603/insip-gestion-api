<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\Departamento;
use App\Exceptions\EmpleadosExceptions\EmpleadoNoEncontradoException;
use App\Exceptions\EmpleadosExceptions\EmpleadoExistenteException;
use App\Exceptions\EmpleadosExceptions\EmpleadoNoActivoException;
use App\Helpers\ApiResponse;

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
        // Validar duplicado por nombre completo y fecha ingreso
        if (Empleado::where('nombre', $data['nombre'])
            ->where('ape_paterno', $data['ape_paterno'])
            ->where('ape_materno', $data['ape_materno'])
            ->where('fecha_ingreso', $data['fecha_ingreso'])
            ->exists()) {
            throw new EmpleadoExistenteException("Ya existe un empleado con esos datos.");
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
                'fecha_ingreso' => $empleado->fecha_ingreso,
                'puesto' => $empleado->puesto,
                'departamento' => $empleado->departamento->nombre ?? 'Sin departamento',
                'status' => $empleado->status,
                'tipo_contrato' => $empleado->tipo_contrato,
            ];
        });

        return ApiResponse::success($datos);
    }
}
