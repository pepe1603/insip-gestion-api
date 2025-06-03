<?php

namespace App\Services;

use App\Services\VacacionesService;
use App\Exports\ExportService;
use App\Helpers\ApiResponse;
use App\Helpers\JsonResponseValidator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;

class ReporteVacacionesService
{
    protected $vacacionesService;
    protected $exportService;

    public function __construct(VacacionesService $vacacionesService, ExportService $exportService)
    {
        $this->vacacionesService = $vacacionesService;
        $this->exportService = $exportService;
    }

    // Generar reporte de vacaciones aprobadas por empleado y ciclo
    public function generarReportePorEmpleadoYCiclo(int $empleadoId, string $ciclo)
    {
        try {
            $response = $this->vacacionesService->getVacacionesAprobadasPorEmpleadoYCiclo($empleadoId, $ciclo);
            if ($response instanceof JsonResponse) {
                // Puedes imprimir el error si quieres depurar:
                // dd($data->getData(true));

                return []; // Retornamos un array vacío para que el export no falle
            }

            return $response; // Retornamos solo los datos
        } catch (\Exception $e) {
            // Loguear el error si es necesario
            return ApiResponse::error($e->getMessage(), 500); // O manejar la excepción específica si la conoces
        }
    }

    // Generar reporte resumen de todas las vacaciones solicitadas
    public function generarReporteResumen()
    {
        try {
            $data = $this->vacacionesService->reporteResumen();
            return $data; // Retornamos solo los datos
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Generar reporte de vacaciones por departamento
    public function generarReportePorDepartamento(int $departamentoId)
    {
        try {
            $response = $this->vacacionesService->reportePorDepartamento($departamentoId);
            if ($response instanceof JsonResponse) {
                // Puedes imprimir el error si quieres depurar:
                // dd($data->getData(true));

                return []; // Retornamos un array vacío para que el export no falle
            }
            return $response; // Retornamos solo los datos
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Generar reporte de los días tomados por mes
    public function generarReporteDiasTomadosPorMes(int $año)
    {
        try {
            $response = $this->vacacionesService->reporteDiasTomadosPorMes($año);

            // Validamos que no sea un JsonResponse (lo cual indica error)
            if ($response instanceof JsonResponse) {
                // Puedes imprimir el error si quieres depurar:
                 dd($response->getData(true));

                return []; // Retornamos un array vacío para que el export no falle
            }

            return $response;
        } catch (\Exception $e) {
            // También retornamos un array vacío para no romper exportToPdf
            // Puedes loguear o lanzar un ApiResponse si estás manejando respuesta HTTP
            return [];
        }
    }


    // Generar reporte de los días tomados por semana
    public function generarReporteDiasPorSemana(int $año)
    {
        try {
            $data = $this->vacacionesService->reporteDiasPorSemana($año);

            return $data; // Retornamos solo l os datos
        } catch (\Exception $e) {

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Generar reporte de los empleados con más días solicitados
    public function generarReporteTopEmpleados(int $limit = 5)
    {
        try {
            $data = $this->vacacionesService->reporteTopEmpleados($limit);
           //dd($data); // Para depurar y ver el resultado
            return $data; // Retornamos solo los datos
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // Generar reporte de vacaciones por periodo
    public function generarReportePorPeriodo($desde, $hasta)
    {
        try {
            $data = $this->vacacionesService->getPorPeriodo($desde , $hasta);
            return $data; // Retornamos solo los datos
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
