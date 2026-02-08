<?php

namespace App\Http\Controllers\Api\Vacaciones;

use App\Http\Controllers\Controller;
use App\Services\EstadoSolicitudService;
use Illuminate\Http\Request;

class EstadoSolicitudController extends Controller
{

    protected $estadoSolicitudService;

    public function __construct(EstadoSolicitudService $estadoSolicitudService)
    {
        $this->estadoSolicitudService = $estadoSolicitudService;
    }

    public function index()
    {
        return $this->estadoSolicitudService->all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //dd($request->all());
        $data = $request->validate([
            'estado' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        return $this->estadoSolicitudService->create($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->estadoSolicitudService->find($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'estado' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        return $this->estadoSolicitudService->update($id, $data);
    }

    public function patch(Request $request, string $id)
    {
        $data = $request->only(['estado']);
        return $this->estadoSolicitudService->updatePartial($id, $data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return $this->estadoSolicitudService->delete($id);
    }
}
