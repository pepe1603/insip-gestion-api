<?php

namespace App\Services;

use App\Models\Departamento;
use App\Exceptions\DepartamentosExceptions\DepartamentoNoEncontradoException;
use App\Exceptions\DepartamentosExceptions\DepartamentoExistenteException;
use App\Helpers\ApiResponse;

class DepartamentoService
{
    // Obtener todos los departamentos
    public function all()
    {
        $departamentos = Departamento::all();

        if ($departamentos->isEmpty()) {
            return ApiResponse::error('No se encontraron departamentos.', 404);
        }

        return ApiResponse::success($departamentos);
    }

    // Obtener un departamento por ID
    public function find($id)
    {
        $departamento = Departamento::find($id);
        if (!$departamento) {
            throw new DepartamentoNoEncontradoException();
        }

        return ApiResponse::success($departamento);
    }

    // Crear un nuevo departamento
    public function create(array $data)
    {
        // Verificar si el departamento ya existe (ejemplo: por nombre único)
        if (Departamento::where('nombre', $data['nombre'])->exists()) {
            throw new DepartamentoExistenteException();
        }

        $departamento = Departamento::create($data);
        return ApiResponse::success($departamento);
    }

    // Actualizar un departamento por ID
    public function update($id, array $data)
    {
        $departamento = Departamento::find($id);
        if (!$departamento) {
            throw new DepartamentoNoEncontradoException();
        }

        $departamento->update($data);
        return ApiResponse::success($departamento);
    }

    //Actualizar parcialmente un departamento
    public function updatePartial($id, array $data)
    {
        $departamento = Departamento::find($id);
        if (!$departamento) {
            throw new DepartamentoNoEncontradoException();
        }

        $departamento->update($data);
        return ApiResponse::success($departamento);
    }

    // Eliminar un departamento
    public function delete($id)
    {
        $departamento = Departamento::find($id);
        if (!$departamento) {
            throw new DepartamentoNoEncontradoException();
        }

        $departamento->delete();
        return ApiResponse::send(204, ['message' => 'Departamento eliminado correctamente.']);
    }

    //metodo genera un reporte de todos los departamentos
    public function generarReporte()
    {
        $departamentos = Departamento::all();
        if ($departamentos->isEmpty()) {
            return [];
        }

        return $departamentos->toArray();
    }
}
