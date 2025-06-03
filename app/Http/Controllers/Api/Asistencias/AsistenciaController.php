<?php

namespace App\Http\Controllers\Api\Asistencias;

use App\Exceptions\EmpleadosExceptions\EmpleadoNoEncontradoException;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use Illuminate\Http\Request;
use App\Services\AsistenciaService;

class AsistenciaController extends Controller
{
    protected $asistenciaService;

    private const HORA_RULE = 'nullable|date_format:H:i';

    public function __construct(AsistenciaService $asistenciaService)
    {
        $this->asistenciaService = $asistenciaService;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);
        return $this->asistenciaService->all($perPage);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'empleado_id' => 'required',
                'tipo_asistencia_id' => 'required',
                'hora_entrada' => 'required|date_format:H:i',
                'hora_salida' => 'required|date_format:H:i',

            ]);

            return $this->asistenciaService->create($data);
        } catch (EmpleadoNoEncontradoException $e) {
            return ApiResponse::error($e->getMessage(), 404); // O código de estado 400 según lo que desees
        }
    }

    public function show(string $id)
    {
        return $this->asistenciaService->find($id);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'empleado_id' => 'nullable|exists:empleados,id',
            'tipo_asistencia_id' => 'nullable|exists:tipo_asistencia,id',
            'fecha' => 'nullable|date',
            'hora_entrada' => 'nullable|date_format:H:i',
            'hora_salida' => 'nullable|date_format:H:i',
            'observaciones' => 'nullable|string|max:255',
        ]);

        return $this->asistenciaService->update($id, $data);
    }

    public function destroy(string $id)
    {
        Asistencia::findOrFail($id);
        return $this->asistenciaService->delete($id);
    }

    public function porRangoFechas(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'perPage' => 'nullable|integer|min:1',
        ]);
        return $this->asistenciaService->getAsistenciasPorRangoFechas(
            $request->fecha_inicio,
            $request->fecha_fin,
            $request->get('perPage')
        );
    }

    public function porEmpleado($empleadoId)
    {
        $asistencias =  $this->asistenciaService->getAsistenciasPorEmpleado($empleadoId);

        return ApiResponse::success($asistencias);
    }

    public function porFecha($fecha)
    {
        return $this->asistenciaService->getAsistenciasPorFecha($fecha);
    }

    public function porMes(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer|min:2000|max:' . now()->year,
            'mes' => 'required|integer|min:1|max:12',
        ]);
        return $this->asistenciaService->getAsistenciasPorMes($request->anio, $request->mes);
    }

    public function porTipoAsistencia($tipoAsistenciaId)
    {
        return $this->asistenciaService->getAsistenciasPorTipoAsistencia($tipoAsistenciaId);
    }

    public function porEmpleadoYFecha($empleadoId, $fecha, Request $request)
    {
        $perPage = $request->get('perPage', 10);
        return $this->asistenciaService->getAsistenciasPorEmpleadoYFecha($empleadoId, $fecha, $perPage);
    }

    public function porDepartamento($departamentoId, Request $request)
    {
        $perPage = $request->get('perPage', 10);
        return $this->asistenciaService->getAsistenciasPorDepartamento($departamentoId, $perPage);
    }
}
