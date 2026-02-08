<?php

namespace App\Http\Controllers\Api\Vacaciones;

use App\Http\Controllers\Controller;
use App\Services\VacacionesOficialesService;
use Illuminate\Http\Request;

class VacacionesOficialesController extends Controller
{

    protected $vacacionesOficialesService;

    public function __construct(VacacionesOficialesService $vacacionesOficialesService)
    {
        $this->vacacionesOficialesService = $vacacionesOficialesService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->vacacionesOficialesService->all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tiempo_servicio' => 'required|integer',
            'dias_vacaciones' => 'required|integer',
        ]);

        return $this->vacacionesOficialesService->create($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->vacacionesOficialesService->find($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'tiempo_servicio' => 'required|integer',
            'dias_vacaciones' => 'required|integer',
        ]);
        return $this->vacacionesOficialesService->update($id, $data);
    }

    public function patch(Request $request, string $id)
    {
        $data = $request->only(['tiempo_servicio', 'dias_vacaciones']);
        return $this->vacacionesOficialesService->updatePartial($id, $data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return $this->vacacionesOficialesService->delete($id);
    }
}
