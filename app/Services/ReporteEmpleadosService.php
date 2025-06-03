<?php

namespace App\Services;

use App\Helpers\ApiResponse;
use App\Services\EmpleadoService;
use App\Exports\ExportService;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteEmpleadosService
{
    protected $empleadoService;
    protected $exportService;

    public function __construct(EmpleadoService $empleadoService, ExportService $exportService)
    {
        $this->empleadoService = $empleadoService;
        $this->exportService = $exportService;
    }

    public function generarReporteEmpleados()
    {
        return $this->empleadoService->getEmpleadosConDetalles();
    }

    public function exportarReporteEmpleados(string $format, string $filename = 'reporte_empleados', string $view = 'exports.empleados')
    {
        $empleados = $this->generarReporteEmpleados();

        if ($empleados->getStatusCode() !== 200) {
            return $empleados;
        }

        $data = $empleados->getData(true)['data'];

        switch ($format) {
            case 'excel':
                return $this->exportService->exportToExcel($data, $filename);
            case 'csv':
                return $this->exportService->exportToCsv($data, $filename);
            case 'pdf':
                return $this->exportPdf($data, $filename, $view);
            default:
                return ApiResponse::error('Formato de exportación no soportado.', 400);
        }
    }

    private function exportPdf(array $data, string $filename, string $view)
    {
        $pdf = Pdf::loadView($view, ['data' => $data]);
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        file_put_contents($tempFile, $pdf->output());
        return response()->download($tempFile, $filename . '.pdf')->deleteFileAfterSend(true);
    }
}
