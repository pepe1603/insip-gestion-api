<?php

namespace App\Http\Controllers\Api\Asistencias;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TipoAsistenciaService;

class TipoAsistenciaController extends Controller
{
    protected $tipoAsistenciaService;

    public function __construct(TipoAsistenciaService $tipoAsistenciaService)
    {
        $this->tipoAsistenciaService = $tipoAsistenciaService;
    }

    public function index()
    {
        return $this->tipoAsistenciaService->all();
    }

    public function show($id)
    {
        return $this->tipoAsistenciaService->find($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        return $this->tipoAsistenciaService->create($data);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        return $this->tipoAsistenciaService->update($id, $data);
    }

    public function patch(Request $request, $id)
    {
        $data = $request->only(['nombre']);
        return $this->tipoAsistenciaService->updatePartial($id, $data);
    }

    public function destroy($id)
    {
        return $this->tipoAsistenciaService->delete($id);
    }
}
