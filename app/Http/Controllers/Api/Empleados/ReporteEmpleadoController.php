<?php

namespace App\Http\Controllers\Api\Empleados;

use App\Http\Controllers\Controller;
use App\Services\ReporteEmpleadosService;
use App\Exports\ExportService;
use Illuminate\Http\Request;

class ReporteEmpleadoController extends Controller
{
    protected $reporteEmpleadosService;
    protected $exportService;

    public function __construct(ReporteEmpleadosService $reporteEmpleadosService, ExportService $exportService)
    {
        $this->reporteEmpleadosService = $reporteEmpleadosService;
        $this->exportService = $exportService;
    }



    /**
     * Generar el reporte de empleados y exportarlo en el formato solicitado.
     */
    public function exportarReporte(Request $request)
    {
        $format = $request->query('format', 'pdf'); // Formato predeterminado: pdf
        $filename = $request->query('filename', 'reporte_empleados'); // Nombre de archivo predeterminado

        $empleadosData = $this->reporteEmpleadosService->generarReporteEmpleados();


        if ($empleadosData->getStatusCode() !== 200) {
            return $empleadosData;
        }

        $data = $empleadosData->getData(true)['data'];

        switch ($format) {
            case 'excel':
                return $this->exportService->exportToExcel($data, $filename);
            case 'csv':
                return $this->exportService->exportToCsv($data, $filename);
            case 'pdf':
                return $this->exportService->exportToPdf($data, $filename, 'exports.empleados');

            default:
                return response()->json(['error' => 'Formato de exportación no soportado'], 400);
        }
    }

}
