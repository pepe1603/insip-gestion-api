<?php

namespace App\Services;

use App\Models\VacacionesOfficiales;
use App\Exceptions\VacacionesOficialesExceptions\VacacionesOficialesNoEncontradasException;
use App\Exceptions\VacacionesOficialesExceptions\VacacionesOficialesExistenteException;
use App\Helpers\ApiResponse;

class VacacionesOficialesService
{
    // Obtener todas las vacaciones oficiales
    public function all()
    {
        $vacaciones = VacacionesOfficiales::all();

        if ($vacaciones->isEmpty()) {
            return ApiResponse::error('No se encontraron vacaciones oficiales.', 404);
        }

        return ApiResponse::success($vacaciones);
    }

    // Obtener vacaciones oficiales por ID
    public function find($id)
    {
        $vacaciones = VacacionesOfficiales::find($id);
        if (!$vacaciones) {
            throw new VacacionesOficialesNoEncontradasException();
        }

        return ApiResponse::success($vacaciones);
    }

    // Crear nuevas vacaciones oficiales
    public function create(array $data)
    {
        // Verificar si las vacaciones oficiales ya existen (ejemplo: por tiempo_servicio único)
        if (VacacionesOfficiales::where('tiempo_servicio', $data['tiempo_servicio'])->exists()) {
            throw new VacacionesOficialesExistenteException();
        }

        $vacaciones = VacacionesOfficiales::create($data);
        return ApiResponse::success($vacaciones);
    }

    // Actualizar vacaciones oficiales por ID
    public function update($id, array $data)
    {
        $vacaciones = VacacionesOfficiales::find($id);
        if (!$vacaciones) {
            throw new VacacionesOficialesNoEncontradasException();
        }

        $vacaciones->update($data);
        return ApiResponse::success($vacaciones);
    }

    //Actualizar parcialmente vacaciones oficiales por ID
    public function updatePartial($id, array $data)
    {
        $vacaciones = VacacionesOfficiales::find($id);
        if (!$vacaciones) {
            throw new VacacionesOficialesNoEncontradasException();
        }

        $vacaciones->update($data);
        return ApiResponse::success($vacaciones);
    }

    // Eliminar vacaciones oficiales
    public function delete($id)
    {
        $vacaciones = VacacionesOfficiales::find($id);
        if (!$vacaciones) {
            throw new VacacionesOficialesNoEncontradasException();
        }

        $vacaciones->delete();
        return ApiResponse::send(204, ['message' => 'Vacaciones oficiales eliminadas correctamente.']);
    }
}
