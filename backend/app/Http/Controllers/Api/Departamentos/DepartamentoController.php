<?php

namespace App\Http\Controllers\Api\Departamentos;

use App\Exports\ExportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DepartamentoService;

class DepartamentoController extends Controller
{
    protected $departamentoService;
    protected $exportService;

    public function __construct(DepartamentoService $departamentoService, ExportService $exportService)
    {
        $this->departamentoService = $departamentoService;
        $this->exportService = $exportService;
    }

     /**
     * Display a listing of the resource.
     */
    public function index()
    {
       return $this->departamentoService->all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        return $this->departamentoService->create($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        return $this->departamentoService->find($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        return $this->departamentoService->update($id, $data);
    }


    public function patch(Request $request, string $id)
    {

        $data = $request->only(['nombre']);
        return $this->departamentoService->updatePartial($id, $data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return $this->departamentoService->delete($id);
    }

    //exportar departamentos
    public function exportarDepartamentos()
    {
        $data =  $this->departamentoService->generarReporte();

        return $this->exportService->exportToPdf($data, 'reporte_departamentos', 'exports.departamentos');

    }
        //exportar departamentos
    public function exportar()
    {
        $data =  $this->departamentoService->generarReporte();

        // Expoertasrt en diferentesds formatos


        return $this->exportService->exportToPdf($data, 'reporte_departamentos', 'exports.departamentos');


    }
}
