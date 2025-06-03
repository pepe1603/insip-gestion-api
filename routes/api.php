<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Asistencias\TipoAsistenciaController;
use App\Http\Controllers\Api\Departamentos\DepartamentoController;
use App\Http\Controllers\Api\Vacaciones\EstadoSolicitudController;
use App\Http\Controllers\Api\Vacaciones\VacacionesOficialesController;

use App\Http\Controllers\Api\Asistencias\AsistenciaController;
use App\Http\Controllers\Api\Asistencias\ReporteAsistenciaController;
use App\Http\Controllers\Api\Empleados\EmpleadoController;
use App\Http\Controllers\Api\Empleados\ReporteEmpleadoController;
use App\Http\Controllers\Api\Vacaciones\ReporteVacacionesController;
use App\Http\Controllers\Api\Vacaciones\VacacionesController;

// Envolver todo dentro del middleware 'api'
// y el prefijo 'api' para las rutas de la API
// y asegurarse de que las rutas estén agrupadas correctamente para evitar conflictos dde nombres
// y mantener una estructura clara y organizada evitando que cuando ocurra un error
// se confunda con el nombre de la ruta y el controlador lanze un error/excepción
// y no se confunda con el nombre de la ruta y el controlador
Route::middleware('api')->group(function () {

    // Rutas para Tipos de Asistencia
    Route::prefix('tipos-asistencia')->group(function () {
        Route::get('/', [TipoAsistenciaController::class, 'index']);
        Route::get('/{id}', [TipoAsistenciaController::class, 'show']);
        Route::post('/', [TipoAsistenciaController::class, 'store']);
        Route::put('/{id}', [TipoAsistenciaController::class, 'update']);
        Route::patch('/{id}', [TipoAsistenciaController::class, 'patch']);
        Route::delete('/{id}', [TipoAsistenciaController::class, 'destroy']);
    });

    // Rutas para Departamentos
    Route::prefix('departamentos')->group(function () {
        Route::get('/', [DepartamentoController::class, 'index']);
        Route::get('/generar-reporte', [DepartamentoController::class, 'exportarDepartamentos']);
        Route::get('/{id}', [DepartamentoController::class, 'show']);
        Route::post('/', [DepartamentoController::class, 'store']);
        Route::put('/{id}', [DepartamentoController::class, 'update']);
        Route::patch('/{id}', [DepartamentoController::class, 'patch']);
        Route::delete('/{id}', [DepartamentoController::class, 'destroy']);
    });

    // Rutas para Estados de Solicitud
    Route::prefix('estados-solicitud')->group(function () {
        Route::get('/', [EstadoSolicitudController::class, 'index']);
        Route::get('/{id}', [EstadoSolicitudController::class, 'show']);
        Route::post('/', [EstadoSolicitudController::class, 'store']);
        Route::put('/{id}', [EstadoSolicitudController::class, 'update']);
        Route::patch('/{id}', [EstadoSolicitudController::class, 'patch']);
        Route::delete('/{id}', [EstadoSolicitudController::class, 'destroy']);
    });

    // Rutas para Vacaciones Oficiales por legislación
    Route::prefix('vacaciones-oficiales')->group(function () {
        Route::get('/', [VacacionesOficialesController::class, 'index']);
        Route::get('/{id}', [VacacionesOficialesController::class, 'show']);
        Route::post('/', [VacacionesOficialesController::class, 'store']);
        Route::put('/{id}', [VacacionesOficialesController::class, 'update']);
        Route::patch('/{id}', [VacacionesOficialesController::class, 'patch']);
        Route::delete('/{id}', [VacacionesOficialesController::class, 'destroy']);
    });

    //---------------- section asistencias -----------------

    Route::prefix('asistencias')->group(function () {
        Route::get('/', [AsistenciaController::class, 'index']);
        Route::post('/', [AsistenciaController::class, 'store']);
        Route::get('/{id}', [AsistenciaController::class, 'show']);
        Route::put('/{id}', [AsistenciaController::class, 'update']);
        Route::delete('/{id}', [AsistenciaController::class, 'destroy']);

    });

    Route::prefix('reporte-asistencias')->group(function () {
        Route::get('/exportar', [ReporteAsistenciaController::class, 'exportarTodo']);
        Route::get('/por-rango', [ReporteAsistenciaController::class, 'porRangoFechas']);
        Route::get('/por-fecha', [ReporteAsistenciaController::class, 'porFecha']);
        Route::get('/por-mes', [ReporteAsistenciaController::class, 'porMes']);
        Route::get('/por-tipo-asistencia', [ReporteAsistenciaController::class, 'porTipoAsistencia']);
        Route::get('/por-empleado', [ReporteAsistenciaController::class, 'porEmpleado']);
        Route::get('/por-empleado-fecha', [ReporteAsistenciaController::class, 'porEmpleadoYFecha']);
    });

    /*
     * Ejemplos de cómo se usan ahora:
        GET /api/reporte-asistencias/por-empleado?empleado_id=2&format=pdf

        GET /api/reporte-asistencias/por-mes?anio=2024&mes=4&format=excel

        GET /api/reporte-asistencias/por-rango?fecha_inicio=2024-04-01&fecha_fin=2024-04-30&format=csv
     */



    //----------------- section de empleados -----------------

    Route::prefix('empleados')->group(function () {
        Route::get('/', [EmpleadoController::class, 'index']);
        Route::get('/{id}', [EmpleadoController::class, 'show']);
        Route::post('/', [EmpleadoController::class, 'store']);
        Route::put('/{id}', [EmpleadoController::class, 'update']);
        Route::patch('/{id}', [EmpleadoController::class, 'patch']);
        Route::delete('/{id}', [EmpleadoController::class, 'destroy']);
        Route::get('/departamento/{departamentoId}', [EmpleadoController::class, 'porDepartamento']);
        Route::get('/activos', [EmpleadoController::class, 'activos']);
        Route::get('/buscar', [EmpleadoController::class, 'buscar']);
        Route::put('/{id}/cambiar-status', [EmpleadoController::class, 'cambiarStatus']);
    });

    Route::prefix('reporte-empleados')->group(function () {
        // Rutas existentes para empleados...
        // Ruta para exportar el reporte de empleados
        Route::get('/exportar', [ReporteEmpleadoController::class, 'exportarReporte']);
    });

    //---------------- section de vacaciones ------------------

    Route::prefix('vacaciones')->group(function () {
        Route::get('/', [VacacionesController::class, 'index']);
        Route::post('/', [VacacionesController::class, 'store']);
        Route::get('/pendientes', [VacacionesController::class, 'pendientes'])->name('vacaciones.pendientes');

        Route::get('/{id}', [VacacionesController::class, 'show']);
        Route::put('/{id}', [VacacionesController::class, 'update']);
        Route::delete('/{id}', [VacacionesController::class, 'destroy']);

        Route::post('/{id}/aprobar', [VacacionesController::class, 'aprobar']);
        Route::post('/{id}/rechazar', [VacacionesController::class, 'rechazar']);
        Route::post('/{id}/cancelar', [VacacionesController::class, 'cancelar']);

        // Rutas para obtener vacaciones por diferentes criterios

        Route::get('/empleado/{empleadoId}', [VacacionesController::class, 'porEmpleado']);
        Route::get('/estado/{estadoId}', [VacacionesController::class, 'porEstado']);
        Route::get('/periodo/{desde}/{hasta}', [VacacionesController::class, 'porPeriodo']);
        Route::get('/disponibilidad/{empleadoId}', [VacacionesController::class, 'disponibilidad']);
    });

    Route::prefix('reporte-vacaciones')->group(function() {

        // Ejemplo de uso: /api/reporte-vacaciones/empleado-ciclo?empleado_id=1&ciclo=2024
        Route::get('/empleado-ciclo', [ReporteVacacionesController::class, 'porEmpleadoYCiclo']);

        // Ejemplo de uso: /api/reporte-vacaciones/departamento?departamento_id=1
        Route::get('/departamento', [ReporteVacacionesController::class, 'porDepartamento']);

        // Ejemplo de uso: /api/reporte-vacaciones/dias-tomados-mes?año=2024
        Route::get('/dias-tomados-mes', [ReporteVacacionesController::class, 'porDiasTomadosPorMes']);

        // Ejemplo de uso: /api/reporte-vacaciones/dias-tomados-semana?año=2024
        Route::get('/dias-tomados-semana', [ReporteVacacionesController::class, 'porDiasTomadosPorSemana']);

        // Ejemplo de uso: /api/reporte-vacaciones/periodo?desde=YYYY-MM-DD&hasta=YYYY-MM-DD
        Route::get('/periodo', [ReporteVacacionesController::class, 'porPeriodo']);

                // Ejemplo de uso: /api/reporte-vacaciones/resumen
        Route::get('/resumen', [ReporteVacacionesController::class, 'porResumenVacacionesSolicitadas']);

        // Ejemplo de uso: /api/reporte-vacaciones/top-empleados?limit=10
        Route::get('/top-empleados', [ReporteVacacionesController::class, 'porTopEmpleados']);




    });

});

