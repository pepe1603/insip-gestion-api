<?php

namespace App\Http\Controllers\Api\Empleados;

use App\Models\Departamento;
use Illuminate\Http\Request;
use App\Models\Empleado;
use App\Services\EmpleadoService;
use App\Http\Controllers\Controller;

class EmpleadoController extends Controller
{
    protected $empleadoService;

    public function __construct(EmpleadoService $empleadoService)
    {
        $this->empleadoService = $empleadoService;
    }

    // GET /api/empleados
    public function index()
    {
        //retornamos todos los empleadossin paginacion del servicio
        return $this->empleadoService->all();
    }

    // GET /api/empleados/{id}
    public function show($id)
    {
        return $this->empleadoService->find($id);
    }

    // POST /api/empleados
    public function store(Request $request)
    {
            $data = $request->validate([
                'nombre'            => 'required|string|max:255',
                'ape_paterno'       => 'required|string|max:255',
                'ape_materno'       => 'required|string|max:255',
                'fecha_ingreso'     => 'required|date',
                'puesto'            => 'required|string|max:255',
                'email'             => 'required|string|email|max:255|unique:empleados',
                'telefono'          => 'string|max:25',
                'departamento_id'   => 'required|exists:departamentos,id',
                'status'            => 'required|in:ACTIVO,INACTIVO',
                'tipo_contrato'     => 'required|in:TIEMPO_COMPLETO,MEDIO_TIEMPO,TEMPORAL',
            ]);

        return $this->empleadoService->create($data);
    }

    // PUT /api/empleados/{id}
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre'         => 'required|string|max:255',
            'ape_paterno'    => 'required|string|max:255',
            'ape_materno'    => 'required|string|max:255',
            'fecha_ingreso'  => 'required|date',
            'email'          => 'string|email|max:255|unique:empleados,email,' . $id,
            'telefono'       => 'string|max:25',
            'puesto'         => 'required|string|max:255',
            'departamento_id' => 'required|exists:departamentos,id',
            'status'         => 'required|in:ACTIVO,INACTIVO',
            'tipo_contrato'  => 'required|in:TIEMPO_COMPLETO,MEDIO_TIEMPO,TEMPORAL',
        ]);

        return $this->empleadoService->update($id, $data);
    }

    // PATCH /api/empleados/{id}
    public function patch(Request $request, $id)
    {
        $data = $request->only([
            'nombre', 'ape_paterno', 'ape_materno', 'fecha_ingreso',
            'puesto', 'departamento_id', 'status', 'tipo_contrato'
        ]);

        return $this->empleadoService->updatePartial($id, $data);
    }

    // DELETE /api/empleados/{id}
    public function destroy($id)
    {
        return $this->empleadoService->delete($id);
    }

    // GET /api/empleados/departamento/{departamentoId}
    public function porDepartamento($departamentoId, Request $request)
    {
        return $this->empleadoService->getByDepartamento($departamentoId);
    }

    // GET /api/empleados/activos
    public function activos()
    {

        return $this->empleadoService->getActivos();
    }

    // GET /api/empleados/buscar?nombre=juan
    public function buscar(Request $request)
    {
        $nombre = $request->query('nombre');

        return $this->empleadoService->buscarPorNombre($nombre);
    }

    // PUT /api/empleados/{id}/cambiar-status
    public function cambiarStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:ACTIVO,INACTIVO',
        ]);

        return $this->empleadoService->cambiarStatus($id, $data['status']);
    }

    //## Nuevos Métodos para Dashboard y Antigüedad

    /**
     * Obtiene el conteo de empleados por su estado para el dashboard.
     * GET /api/dashboard/empleados/status-counts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatusCounts()
    {
        return $this->empleadoService->getStatusCounts();
    }

    /**
     * Obtiene los empleados recién contratados para el dashboard.
     * GET /api/dashboard/empleados/recien-ingresados?days=X
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentlyHired(Request $request)
    {
        $days = $request->query('days', 30); // Por defecto, últimos 30 días
        $days = (int) $days; // Asegura que sea un entero
        return $this->empleadoService->getRecentlyHired($days);
    }

    /**
     * Obtiene la antigüedad de un empleado específico.
     * GET /api/empleados/{id}/antiguedad
     *
     * @param  int  $id El ID del empleado.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAntiguedad($id)
    {
        return $this->empleadoService->getAntiguedad($id);
    }
}
