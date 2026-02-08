<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Exceptions\AsistenciasExceptions\AsistenciaNoEncontradaException;
use App\Exceptions\AsistenciasExceptions\AsistenciaExistenteException;
use App\Exceptions\AsistenciasExceptions\AsistenciaHoraInvalidaException;
use App\Exceptions\BusinessException;
use App\Exceptions\EmpleadosExceptions\EmpleadoNoActivoException;
use App\Exceptions\EmpleadosExceptions\EmpleadoNoEncontradoException;
use App\Helpers\ApiResponse;
use App\Models\Departamento;

class AsistenciaService
{
    public function all(int $perPage = 10)
    {
        $asistencias = Asistencia::with(['empleado', 'tipoAsistencia'])->paginate($perPage);

        if ($asistencias->isEmpty()) {
            return ApiResponse::error('No se encontraron asistencias.', 404);
        }

        return ApiResponse::success($asistencias);
    }

    public function find($id)
    {
        $asistencia = Asistencia::with(['empleado', 'tipoAsistencia'])->findOrFail($id);
        return ApiResponse::success($asistencia);
    }

    public function create(array $data)
    {
        $empleado = Empleado::find($data['empleado_id']);
        if (!$empleado) {
            throw new EmpleadoNoEncontradoException("El empleado con ID {$data['empleado_id']} no existe.");
        }

        if ($empleado->status !== 'ACTIVO') {
            throw new EmpleadoNoActivoException();
        }
        if (isset($data['hora_salida']) && $data['hora_salida'] < $data['hora_entrada']) {
            throw new AsistenciaHoraInvalidaException();
        }

        //la fecha de asistencia se calcula en base a la fecha actual
        $fecha_actual = now()->format('Y-m-d');
        // Verificar si ya existe una asistencia en la misma fecha con un rango de horas que se solapen
        $colisionExistente = Asistencia::where('empleado_id', $empleado->id)
            ->where('fecha', $fecha_actual)
            ->where(function($query) use ($data) {
                // Verificamos que la entrada de la nueva asistencia no se solape con una ya existente
                $query->where(function($q) use ($data) {
                    $q->where('hora_entrada', '<=', $data['hora_entrada'])
                        ->where('hora_salida', '>=', $data['hora_entrada']);
                })->orWhere(function($q) use ($data) {
                    $q->where('hora_entrada', '<=', $data['hora_salida'])
                        ->where('hora_salida', '>=', $data['hora_salida']);
                });
            })
            ->exists();

        if ($colisionExistente) {
            throw new AsistenciaExistenteException("Ya existe una asistencia registrada para este empleado en este rango de horas.", 409);
        }
//si observaciones es null, se asigna un string vacio
        //si observaciones es null, se asigna un la cadena 'No hay observaciones'
        if (empty($data['observaciones'])) {
            $data['observaciones'] = 'No hay observaciones';
        } else {
            $data['observaciones'] = $data['observaciones'];
        }


        $asistencia = Asistencia::create([
            'empleado_id' => $data['empleado_id'],
            'tipo_asistencia_id' => $data['tipo_asistencia_id'],
            'hora_entrada' => now()->parse($data['hora_entrada'])->format('H:i:s'), // Formatear solo la hora
            'hora_salida' => now()->parse($data['hora_salida'])->format('H:i:s'),   // Formatear solo la hora
            'fecha' => $fecha_actual,
            'observaciones' => $data['observaciones'],
        ]);
        return ApiResponse::success($asistencia->fresh());
    }

    public function update($id, array $data)
    {
        $asistencia = Asistencia::findOrFail($id);

        if (isset($data['empleado_id']) && $data['empleado_id'] !== $asistencia->empleado_id) {
            $empleado = Empleado::findOrFail($data['empleado_id']);
            if ($empleado->status !== 'ACTIVO') {
                throw new EmpleadoNoActivoException();
            }
        }

        if (!isset($data['hora_entrada']) || !isset($data['hora_salida'])) {
            throw new BusinessException("La hora de entrada y salida son obligatorias.");
        }

        if (isset($data['hora_salida']) && isset($data['hora_entrada']) && $data['hora_salida'] < $data['hora_entrada']) {
            throw new AsistenciaHoraInvalidaException();
        }

        if (Asistencia::where('empleado_id', $asistencia->empleado_id)->where('fecha', $data['fecha'])->where('id', '!=', $id)->exists()) {
            throw new AsistenciaExistenteException("Ya existe una asistencia registrada para este empleado en esta fecha.", 409);
        }

        $asistencia->update($data);
        return ApiResponse::success($asistencia->fresh());
    }

    public function delete($id)
    {
       try{

        Asistencia::findOrFail($id)->delete();
        return ApiResponse::send(204, ['message' => 'Asistencia eliminada correctamente.']);
       }catch (\Exception $e) {
        // Loguea el error para investigación
        //Log::error('Error inesperado en algunaOperacionRiesgosa: ' . $e->getMessage());
        // Lanza una excepción genérica o una personalizada
        throw new \Exception('Ocurrió un error inesperado al procesar la operación.', 500);
    }
    }

    public function getAsistenciasPorRangoFechas(string $fechaInicio, string $fechaFin)
    {
        $asistencias = Asistencia::with(['empleado', 'tipoAsistencia'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get();

        if ($asistencias->isEmpty()) {
            return [];
        }

        return $asistencias->toArray();
    }

    public function getAsistenciasPorEmpleado(int $empleadoId)
    {
        try {
            $asistencias = Asistencia::with(['empleado', 'tipoAsistencia'])
            ->where('empleado_id', $empleadoId)
            ->get();

        if ($asistencias->isEmpty()) {
            return [];
        }


        return $asistencias->toArray();

        } catch (\Exception $e) {
            // Manejo de excepciones
            return ApiResponse::error('Error al obtener las asistencias: ' . $e->getMessage(), 500);
        }
    }

    public function getAsistenciasPorFecha(string $fecha)
    {
        $asistencias = Asistencia::with(['empleado', 'tipoAsistencia'])
            ->where('fecha', $fecha)
            ->get();

        if ($asistencias->isEmpty()) {
            return [];
        }

        return $asistencias->toArray();
    }

    public function getAsistenciasPorMes(int $anio, int $mes)
    {
        try {
            $asistencias = Asistencia::with(['empleado', 'tipoAsistencia'])
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->get();

            if ($asistencias->isEmpty()) {
                return [];
            }

            return $asistencias->toArray();
        }catch (\Exception $e) {
            // Manejo de excepciones
            return ApiResponse::error('Error al obtener las asistencias: ' . $e->getMessage(), 500);
        }
    }

    public function getAsistenciasPorTipoAsistencia(int $tipoAsistenciaId)
    {
        $asistencias = Asistencia::with(['empleado', 'tipoAsistencia'])
            ->where('tipo_asistencia_id', $tipoAsistenciaId)
            ->get();

        if ($asistencias->isEmpty()) {
            return ApiResponse::error('No se encontraron asistencias para el tipo de asistencia especificado.', 404);
        }

        return ApiResponse::success($asistencias);
    }

    public function getAsistenciasPorEmpleadoYFecha(int $empleadoId, string $fecha, int $perPage = 10)
    {
        Empleado::findOrFail($empleadoId); // Asegurarse de que el empleado existe

        $asistencias = Asistencia::with(['empleado', 'tipoAsistencia'])
            ->where('empleado_id', $empleadoId)
            ->where('fecha', $fecha)
            ->paginate($perPage);

        if ($asistencias->isEmpty()) {
            return ApiResponse::error('No se encontraron asistencias para este empleado en esta fecha.', 404);
        }

        return ApiResponse::success($asistencias);
    }

    public function getAsistenciasPorDepartamento(int $departamentoId)
    {

        //Corregir metood de exportacion para generrar Fromatos PDF, CSV, excel.etc..
        Departamento::findOrFail($departamentoId); // Asegurarse de que el departamento existe

        $asistencias = Asistencia::with(['empleado', 'tipoAsistencia', 'empleado.departamento'])
            ->whereHas('empleado', function ($query) use ($departamentoId) {
                $query->where('departamento_id', $departamentoId);
            });

            dd($asistencias);

        if ($asistencias->isEmpty()) {
            return [];
        }

        return ApiResponse::success($asistencias);
    }
}
