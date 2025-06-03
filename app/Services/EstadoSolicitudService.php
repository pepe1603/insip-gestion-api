<?php

namespace App\Services;

use App\Models\EstadoSolicitud;
use App\Exceptions\EstadosSolicitudExceptions\EstadoSolicitudNoEncontradoException;
use App\Exceptions\EstadosSolicitudExceptions\EstadoSolicitudExistenteException;
use App\Helpers\ApiResponse;

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
}
