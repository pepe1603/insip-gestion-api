<?php

namespace App\Http\Controllers\Api\Asistencias;

use Carbon\Carbon;
use App\Models\Asistencia;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Services\AsistenciaService;
use App\Http\Controllers\Controller;
use App\Exceptions\EmpleadosExceptions\EmpleadoNoEncontradoException;

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

    //--------------- Mertodohos Dahsboard -----------
     /**
     * Obtiene todas las asistencias registradas para el día actual.
     * Incluye la información del empleado y el tipo de asistencia.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAsistenciasHoy(Request $request)
    {
        try {
            $today = Carbon::today();
            // Carga las relaciones de empleado y tipoAsistencia para obtener sus datos directamente
            $asistenciasHoy = Asistencia::with('empleado', 'tipoAsistencia')
                                        ->whereDate('fecha', $today)
                                        ->orderBy('hora_entrada', 'asc') // Opcional: ordenar por hora de entrada
                                        ->get();

            // Puedes formatear los datos si es necesario, o dejar que el frontend lo haga
            $formattedAsistencias = $asistenciasHoy->map(function ($asistencia) {
                return [
                    'id' => $asistencia->id,
                    'empleado' => $asistencia->empleado->getFullName(), // Usa el método del modelo Empleado
                    'tipo_asistencia' => $asistencia->tipoAsistencia->nombre, // Nombre del tipo de asistencia
                    'fecha' => $asistencia->getFechaFormatted(),
                    'hora_entrada' => $asistencia->getHoraEntradaFormatted(),
                    'hora_salida' => $asistencia->getHoraSalidaFormatted(),
                    'observaciones' => $asistencia->getObservacionesFormatted(),
                    'status' => $asistencia->getStatus(),
                    'created_at' => $asistencia->created_at,
                    'updated_at' => $asistencia->updated_at,
                ];
            });

            return response()->json([
                'message' => 'Asistencias del día de hoy obtenidas exitosamente.',
                'data' => $formattedAsistencias
            ], 200);

        } catch (\Exception $e) {
            // Log the error for debugging
            //\Log::error("Error al obtener asistencias de hoy: " . $e->getMessage());
            return response()->json([
                'message' => 'Hubo un error al procesar tu solicitud para obtener las asistencias de hoy.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene las últimas N asistencias registradas.
     * Permite un parámetro 'limit' para controlar la cantidad de resultados.
     * Incluye la información del empleado y el tipo de asistencia.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLatestAsistencias(Request $request)
    {
        try {
            // Validar que 'limit' sea un número entero y positivo
            $limit = $request->query('limit', 10); // Valor por defecto: 10
            $limit = max(1, (int)$limit); // Asegura que sea al menos 1

            $latestAsistencias = Asistencia::with('empleado', 'tipoAsistencia')
                                            ->orderBy('created_at', 'desc')
                                            ->limit($limit)
                                            ->get();

            $formattedAsistencias = $latestAsistencias->map(function ($asistencia) {
                return [
                    'id' => $asistencia->id,
                    'empleado' => $asistencia->empleado->getFullName(),
                    'tipo_asistencia' => $asistencia->tipoAsistencia->nombre,
                    'fecha' => $asistencia->getFechaFormatted(),
                    'hora_entrada' => $asistencia->getHoraEntradaFormatted(),
                    'hora_salida' => $asistencia->getHoraSalidaFormatted(),
                    'observaciones' => $asistencia->getObservacionesFormatted(),
                    'status' => $asistencia->getStatus(),
                    'created_at' => $asistencia->created_at,
                    'updated_at' => $asistencia->updated_at,
                ];
            });

            return response()->json([
                'message' => "Últimas {$limit} asistencias obtenidas exitosamente.",
                'data' => $formattedAsistencias
            ], 200);

        } catch (\Exception $e) {
            //\Log::error("Error al obtener las últimas asistencias: " . $e->getMessage());
            return response()->json([
                'message' => 'Hubo un error al procesar tu solicitud para obtener las últimas asistencias.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
