<?php

namespace App\Http\Controllers\Api\Asistencias;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ReporteAsistenciaService;
use App\Exports\ExportService;

class ReporteAsistenciaController extends Controller
{
    protected $reporteAsistenciaService;
    protected $exportService;

    public function __construct(ReporteAsistenciaService $reporteAsistenciaService, ExportService $exportService)
    {
        $this->reporteAsistenciaService = $reporteAsistenciaService;
        $this->exportService = $exportService;
    }

    private function exportar(Request $request, $data, $filename, $view = null, $extraParams = [])
    {
        $format = $request->query('format', 'pdf');

        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        switch ($format) {
            case 'excel':
                return $this->exportService->exportToExcel($data, $filename);
            case 'csv':
                return $this->exportService->exportToCsv($data, $filename);
            case 'pdf':
            default:
                return $this->exportService->exportToPdf($data, $filename, $view, $extraParams);
        }
    }

    public function porEmpleado(Request $request)
    {
        $request->validate(['empleado_id' => 'required|integer']);
        $empleado_id = $request->query('empleado_id');
        $data = $this->reporteAsistenciaService->generarReportePorEmpleado($empleado_id);
        return $this->exportar($request, $data, 'asistencias_por_empleado', 'exports.por_empleado');
    }

    public function porFecha(Request $request)
    {
        $request->validate(['fecha' => 'required|date']);
        $fecha = $request->query('fecha');
        $data = $this->reporteAsistenciaService->generarReportePorFecha($fecha);
        return $this->exportar($request, $data, 'asistencias_por_fecha', 'exports.por_rango_fechas');
    }

    public function porRangoFechas(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $fecha_inicio = $request->query('fecha_inicio');
        $fecha_fin = $request->query('fecha_fin');
        $data = $this->reporteAsistenciaService->generarReportePorRangoFechas($fecha_inicio, $fecha_fin);
        return $this->exportar($request, $data, 'asistencias_por_rango_fechas', 'exports.por_rango_fechas', [ 'fechaInicio' => $fecha_inicio, 'fechaFin' => $fecha_fin ]);
    }

    public function porTipoAsistencia(Request $request)
    {
        $request->validate(['tipo_asistencia_id' => 'required|integer']);
        $tipo_asistencia_id = $request->query('tipo_asistencia_id');
        $data = $this->reporteAsistenciaService->generarReportePorTipoAsistencia($tipo_asistencia_id);
        return $this->exportar($request, $data, 'asistencias_por_tipo');
    }

    public function porMes(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer',
            'mes' => 'required|integer|between:1,12',
        ]);

        $anio = $request->query('anio');
        $mes = $request->query('mes');
        $data = $this->reporteAsistenciaService->generarReportePorMes($anio, $mes);
        $filename = 'asistencias_mes_' . $anio . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT);
        return $this->exportar($request, $data, $filename, 'exports.por_mes', ['anio' => $anio, 'mes' => $mes]);
    }

    public function porEmpleadoYFecha(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|integer',
            'fecha' => 'required|date',
        ]);

        $empleado_id = $request->query('empleado_id');
        $fecha = $request->query('fecha');
        $data = $this->reporteAsistenciaService->generarReportePorEmpleadoYFecha($empleado_id, $fecha);
        return $this->exportar($request, $data, 'asistencias_empleado_' . $empleado_id . '_' . $fecha);
    }

    public function exportarTodo(Request $request)
    {
        $request->validate([
            'format' => 'required|in:excel,csv,pdf',
            'filename' => 'required|string',
        ]);

        $format = $request->query('format');
        $filename = $request->query('filename');
        $data = $this->reporteAsistenciaService->generarReporteTotal();
        return $this->exportar($request, $data, $filename);
    }
}
