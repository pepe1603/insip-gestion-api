<?php
namespace App\Exports;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GenericExport;
use App\Exports\MultiSheetExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportService
{

    public function exportToExcel(array $data, string $filename): BinaryFileResponse
    {


        if (array_keys($data) !== range(0, count($data) - 1)) {
            return Excel::download(new MultiSheetExport($data), $filename . '.xlsx');
        } else {
            return Excel::download(new GenericExport($data), $filename . '.xlsx');
        }
    }

    public function exportToCsv(array $data, string $filename): BinaryFileResponse
    {
        return Excel::download(new GenericExport($data), $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    //hacer exportToPdf generico tenga el parametro $view sea opcional
    //si no se pasa el $view, usar la vista generica
    //si se pasa el $view, usar la vista que se pasa como parametro
    public function exportToPdf(array $data, string $filename, string $view = 'exports.generic', array $viewData = []): BinaryFileResponse
    {
        // Verificar si la vista existe
        if (!view()->exists($view)) {
            $view = 'exports.generic';
        }

        // Cargar la vista y generar el PDF, fusionando los datos
        $pdf = Pdf::loadView($view, array_merge(['data' => $data], $viewData));

        // Crear un archivo temporal para el PDF
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        file_put_contents($tempFile, $pdf->output());

        // Descargar el archivo PDF y eliminarlo después de enviarlo
        return response()->download($tempFile, $filename . '.pdf')->deleteFileAfterSend(true);
    }
}
