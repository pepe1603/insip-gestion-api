<?php

namespace App\Http\Controllers\Api\Vacaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vacaciones\StoreVacacionesRequest;
use App\Http\Requests\Vacaciones\UpdateVacacionesRequest;
use App\Services\VacacionesService;

class VacacionesController extends Controller
{
    protected $vacacionesService;

    public function __construct(VacacionesService $vacacionesService)
    {
        $this->vacacionesService = $vacacionesService;
    }


    public function index()
    {
        return $this->vacacionesService->all();
    }

    public function store(StoreVacacionesRequest $request)
    {
        return $this->vacacionesService->registrarSolicitud($request->validated());
    }

    public function show($id)
    {
        return $this->vacacionesService->find($id);
    }

    public function update(UpdateVacacionesRequest $request, $id)
    {
        return $this->vacacionesService->update($id, $request->validated());
    }

    public function destroy($id)
    {
        return $this->vacacionesService->delete($id);
    }

    public function aprobar($id)
    {
        return $this->vacacionesService->aprobarSolicitud($id);
    }

    public function rechazar($id)
    {
        return $this->vacacionesService->rechazarSolicitud($id);
    }

    public function cancelar($id)
    {
        return $this->vacacionesService->cancelarSolicitud($id);
    }

    public function porEmpleado($empleadoId)
    {
        return $this->vacacionesService->getByEmpleado($empleadoId);
    }

    public function porEstado($estadoId)
    {
        return $this->vacacionesService->getPorEstado($estadoId);
    }

    public function pendientes()
    {
        return $this->vacacionesService->getPendientes();
    }

    public function porPeriodo($desde, $hasta)
    {
        return  $this->vacacionesService->getPorPeriodo($desde, $hasta);
    }

    public function disponibilidad($empleadoId)
    {
        return $this->vacacionesService->getDisponibilidad($empleadoId);
    }
}
