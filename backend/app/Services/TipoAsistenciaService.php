<?php

namespace App\Services;

use App\Models\TipoAsistencia;
use App\Exceptions\TiposAsistenciaExceptions\TipoAsistenciaNoEncontradaException;
use App\Exceptions\TiposAsistenciaExceptions\TipoAsistenciaExistenteException;
use App\Helpers\ApiResponse;

class TipoAsistenciaService
{
    // Obtener todos los tipos de asistencia
    public function all()
    {
        $tipos = TipoAsistencia::all();

        if ($tipos->isEmpty()) {
            return ApiResponse::error('No se encontraron tipos de asistencia.', 404);
        }

        return ApiResponse::success($tipos);
    }

    // Obtener un tipo de asistencia por ID
    public function find($id)
    {
        /*
            Buscar el tipo de asistencia por ID, Si no se encuentra, lanzará una excepción ModelNotFoundException
            que será capturada en el bloque catch
            y se lanzará una excepción personalizada TipoAsistenciaNoEncontradaException
            Puedes usar el método findOrFail para esto
            o el método find y verificar si es nulo
             */
        $tipo = TipoAsistencia::find($id);
        if (!$tipo) {
            throw new TipoAsistenciaNoEncontradaException();
        }
        // O simplemente usar el método findOrFail
        // que lanzará la excepción automáticamente si no se encuentra
        // el tipo de asistencia
        //$tipo = TipoAsistencia::findOrFail($id);
        return ApiResponse::success($tipo);
    }

    // Crear un nuevo tipo de asistencia
    public function create(array $data)
    {

        // Verificar si el tipo de asistencia ya existe (ejemplo: por nombre único)
        if (TipoAsistencia::where('nombre', $data['nombre'])->exists()) {
            throw new TipoAsistenciaExistenteException();
        }

        $tipo = TipoAsistencia::create($data);
        return ApiResponse::success($tipo);
    }

    // Actualizar un tipo de asistencia por ID
    public function update($id, array $data)
    {
        try {
            $tipo = TipoAsistencia::findOrFail($id);
            $tipo->update($data);
            return ApiResponse::success($tipo);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new TipoAsistenciaNoEncontradaException();
        }
    }

    // Actualizar parcialmente un tipo de asistencia por ID
    public function updatePartial($id, array $data)
    {

        $tipo = TipoAsistencia::find($id);
        if (!$tipo) {
            throw new TipoAsistenciaNoEncontradaException();
        }

        $tipo->update($data);

        return ApiResponse::success($tipo);
    }

    // Eliminar un tipo de asistencia
    public function delete($id)
    {
        try {
            $tipo = TipoAsistencia::findOrFail($id);
            $tipo->delete();
            return ApiResponse::send(204, ['message' => 'Tipo de asistencia eliminado correctamente.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new TipoAsistenciaNoEncontradaException();
        }
    }
}
