<?php

namespace App\Http\Controllers\Api\Vacaciones;

use App\Http\Controllers\Controller;
use App\Exports\ExportService;
use App\Services\VacacionesService;
use Illuminate\Http\Request; // ¡Importar la clase Request!

class ReporteVacacionesController extends Controller
{
    protected $vacacionesService;
    protected $exportService;

    public function __construct(VacacionesService $vacacionesService, ExportService $exportService)
    {
        $this->vacacionesService = $vacacionesService;
        $this->exportService = $exportService;
    }

    /**
     * Método genérico para manejar la exportación a diferentes formatos.
     * Recibe el objeto Request para leer el parámetro 'format'.
     */
    public function exportar(Request $request, $data, string $filename, string $view = null, array $extraParams = [])
    {
        $format = $request->query('format', 'pdf'); // Por defecto 'pdf' si no se especifica

        // Si $data ya es una respuesta JSON, la devuelve directamente (ej. de errores internos)
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
                // Si la vista no se pasa, se usa la genérica del ExportService
                return $this->exportService->exportToPdf($data, $filename, $view, $extraParams);
        }
    }

    /**
     * Genera un reporte de vacaciones por empleado y ciclo.
     * Espera 'empleado_id' y 'ciclo' como query parameters.
     * GET /api/reporte-vacaciones/empleado-ciclo?empleado_id=1&ciclo=2024
     */
    public function porEmpleadoYCiclo(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|integer',
            'ciclo' => 'required|string', // El ciclo puede ser un año (ej. "2024")
            'format' => 'nullable|string|in:pdf,excel,csv', // Permitir el formato de exportación
        ]);

        $empleadoId = $request->query('empleado_id');
        $ciclo = $request->query('ciclo');

        $data = $this->vacacionesService->getVacacionesAprobadasPorEmpleadoYCiclo ($empleadoId, $ciclo); // Llama al servicio

        // Aquí puedes especificar una vista personalizada para este reporte si la tienes
        return $this->exportar($request, $data, 'reporte_vacaciones_empleado_ciclo', 'reportes.reporte_vacaciones_empleado_ciclo');
    }

    /**
     * Genera un reporte de resumen de vacaciones solicitadas.
     * No requiere parámetros de consulta.
     * GET /api/reporte-vacaciones/resumen
     */
    public function porResumenVacacionesSolicitadas(Request $request) // Se inyecta Request para pasarla a exportar
    {
        $request->validate([
            'format' => 'nullable|string|in:pdf,excel,csv',
        ]);

        $data = $this->vacacionesService->reporteResumen();

        // Puedes especificar una vista personalizada si la tienes para el resumen
        return $this->exportar($request, $data, 'reporte_resumen_vacaciones_solicitadas', 'reportes.reporte_vacaciones_resumen');
    }

    /**
     * Genera un reporte de vacaciones por departamento.
     * Espera 'departamento_id' como query parameter.
     * GET /api/reporte-vacaciones/departamento?departamento_id=1
     */
    public function porDepartamento(Request $request)
    {
        $request->validate([
            'departamento_id' => 'required|integer',
            'format' => 'nullable|string',
        ]);

        $departamentoId = $request->query('departamento_id');
        $data = $this->vacacionesService->reportePorDepartamento($departamentoId); // Acceder al 'original' de ApiResponse

        return $this->exportar($request, $data, 'reporte_vacaciones_por_departamento', 'reportes.reporte_vacaciones_por_departamento');
    }

    /**
     * Genera un reporte de los días tomados por mes en un año específico.
     * Espera 'año' como query parameter.
     * GET /api/reporte-vacaciones/dias-tomados-mes?año=2024
     * este emtodo solo
     */
    public function porDiasTomadosPorMes(Request $request)
    {
        $request->validate([
            'año' => 'required|integer|digits:4',
            'format' => 'nullable|string|in:pdf,excel,csv',
        ]);

        $año = $request->query('año');
        $data = $this->vacacionesService->reporteDiasTomadosPorMes($año);

        return $this->exportar($request, $data, 'reporte_vacaciones_por_mes', 'reportes.reporte_vacaciones_dias_por_mes',[
            'año' => $año, // Pasar el año como parámetro adicional si es necesario
        ]);
    }

    /**
     * Genera un reporte de vacaciones de los días tomados por semana en un año específico.
     * Espera 'año' como query parameter.
     * GET /api/reporte-vacaciones/dias-tomados-semana?año=2024
     */
    public function porDiasTomadosPorSemana(Request $request)
    {
        $request->validate([
            'año' => 'required|integer|digits:4',
            'format' => 'nullable|string|in:pdf,excel,csv',
        ]);

        $año = $request->query('año');
        $data = $this->vacacionesService->reporteDiasPorSemana($año);

        return $this->exportar($request, $data, 'reporte_vacaciones_por_semana', 'reportes.reporte_vacaciones_dias_por_semana',[
            'año' => $año, // Pasar el año como parámetro adicional si es necesario
        ]);
    }

    /**
     * Genera un reporte de vacaciones por Top empleados.
     * Espera 'limit' como query parameter (opcional, por defecto 5).
     * GET /api/reporte-vacaciones/top-empleados?limit=10
     */
    public function porTopEmpleados(Request $request)
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1',
            'format' => 'nullable|string',
        ]);

        $limit = $request->query('limit', 5); // Valor por defecto de 5
        $data = $this->vacacionesService->reporteTopEmpleados($limit);

        return $this->exportar($request, $data, 'reporte_top_empleados', 'reportes.reporte_vacaciones_top_empleados', [
            'limit' => $limit, // Pasar el límite como parámetro adicional si es necesario
        ]);
    }

    /**
     * Genera un reporte de vacaciones por periodo de fechas.
     * Espera 'desde' y 'hasta' como query parameters.
     * GET /api/reporte-vacaciones/periodo?desde=2024-01-01&hasta=2024-01-31
     */
    public function porPeriodo(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
            'format' => 'nullable|string|in:pdf,excel,csv',
        ]);

        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $data = $this->vacacionesService->getPorPeriodo($desde, $hasta); // Asegúrate de usar el método correcto del servicio

        return $this->exportar($request, $data, 'reporte_vacaciones_por_periodo', 'reportes.reporte_vacaciones_por_periodo', [
            'desde' => $desde, // Pasar las fechas como parámetros adicionales si es necesario
            'hasta' => $hasta,
        ]);
    }
}
