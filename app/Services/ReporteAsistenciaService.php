<?php
namespace App\Services;

use App\Helpers\ApiResponse;
use App\Exports\ExportService;

class ReporteAsistenciaService
{
    protected $asistenciaService;
    protected $exportService;

    public function __construct(AsistenciaService $asistenciaService, ExportService $exportService)
    {
        $this->asistenciaService = $asistenciaService;
        $this->exportService = $exportService;
    }

    public function generarReporteTotal()
    {
        $response = $this->asistenciaService->all(0); // Obtener todos los registros sin paginación

        // Verificar si la respuesta fue exitosa (código de estado 200)
        if ($response->getStatusCode() !== 200 || $response->getData()->status !== 'success') {
            return $response; // Devolver la respuesta de error directamente
        }

        return $response->getData()->data;
    }

    public function generarReportePorFecha(string $fecha)
    {
        $response = $this->asistenciaService->getAsistenciasPorFecha($fecha);
        // como retorna un APiResponse obntenemos el data directamebnte..
        return $response;
    }

    public function generarReportePorMes(int $anio, int $mes)
    {

        $response = $this->asistenciaService->getAsistenciasPorMes($anio, $mes);

        if (empty($response)) {
            return []; // Devolver un array vacío si no hay datos
        }
        $data = collect($response)->map(function ($asistencia) {
            return [
                'id' => $asistencia['id'],
                'empleado' => $asistencia['empleado']['nombre'] ?? '', // Asegúrate de que exista el nombre
                'fecha' => $asistencia['fecha'],
                'hora_entrada' => $asistencia['hora_entrada'],
                'hora_salida' => $asistencia['hora_salida'],
                'tipo_asistencia' => $asistencia['tipo_asistencia']['nombre'] ?? '', // Asegúrate de que exista el nombre

            ];
        })->toArray();
        return $data;
    }

    public function generarReportePorRangoFechas(string $fechaInicio, string $fechaFin)
    {
        $response = $this->asistenciaService->getAsistenciasPorRangoFechas($fechaInicio, $fechaFin, 0);

        if (empty($response)) {
            return []; // Devolver un array vacío si no hay datos
        }
        $data = collect($response)->map(function ($asistencia) {
            return [
                'id' => $asistencia['id'],
                'empleado' => $asistencia['empleado']['nombre'] ?? '', // Asegúrate de que exista el nombre
                'fecha' => $asistencia['fecha'],
                'hora_entrada' => $asistencia['hora_entrada'],
                'hora_salida' => $asistencia['hora_salida'],
                'tipo_asistencia' => $asistencia['tipo_asistencia']['nombre'] ?? '', // Asegúrate de que exista el nombre

            ];
        })->toArray();

        return $data;
    }

    public function generarReportePorEmpleado(int $empleadoId)
    {
        $asistencias = $this->asistenciaService->getAsistenciasPorEmpleado($empleadoId);

        if (empty($asistencias)) {
            return []; // Devolver un array vacío si no hay datos
        }

        $data = collect($asistencias)->map(function ($asistencia) {
            return [
                'id' => $asistencia['id'],
                'fecha' => $asistencia['fecha'],
                'hora_entrada' => $asistencia['hora_entrada'],
                'hora_salida' => $asistencia['hora_salida'],
                'tipo_asistencia' => $asistencia['tipo_asistencia']['nombre'] ?? '', // Asegúrate de que exista el nombre
                'empleado' => $asistencia['empleado']['nombre'] ?? '', // Asegúrate de que exista el nombre

                'observaciones' => $asistencia['observaciones'] ?? '',
                // Agrega aquí cualquier otro campo que necesites de empleado o tipo de asistencia directamente
            ];
        })->toArray();

        return $data;
    }

    public function generarReportePorEmpleadoYFecha(int $empleadoId, string $fecha)
    {
        $response = $this->asistenciaService->getAsistenciasPorEmpleadoYFecha($empleadoId, $fecha, 0);
        return $response->getData();
    }

    public function generarReportePorTipoAsistencia(int $tipoAsistenciaId)
    {
        $response = $this->asistenciaService->getAsistenciasPorTipoAsistencia($tipoAsistenciaId);
        return $response->getData();
    }
}
