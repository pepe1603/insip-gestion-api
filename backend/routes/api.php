<?php

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\Empleados\EmpleadoController;
use App\Http\Controllers\Api\Vacaciones\VacacionesController;
use App\Http\Controllers\Api\Asistencias\AsistenciaController;
use App\Http\Controllers\Api\Empleados\ReporteEmpleadoController;
use App\Http\Controllers\Api\Asistencias\TipoAsistenciaController;
use App\Http\Controllers\Api\Departamentos\DepartamentoController;
use App\Http\Controllers\Api\Vacaciones\EstadoSolicitudController;
use App\Http\Controllers\Api\Empleados\EmployeeDashboardController;
use App\Http\Controllers\Api\Vacaciones\ReporteVacacionesController;
use App\Http\Controllers\Api\Asistencias\ReporteAsistenciaController;
use App\Http\Controllers\Api\Vacaciones\VacacionesOficialesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/*
|-------------------------------------------------------------------------------------------------------
| Public Routes (Rutas públicas)
|-------------------------------------------------------------------------------------------------------
| Estas rutas no requieren autenticación.
*/

// Endpoint de Verificación de Estado de Salud
Route::match(['OPTIONS'], '/health', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
});

Route::match(['GET', 'HEAD'], '/health', function () {
    return response()->json(['status' => 'ok', 'message' => 'API is healthy'], 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
});

// Endpoint público para obtener el estado de la API
Route::get('/status', [ApiController::class, 'getStatus']);

// Rutas de información de la API
Route::get('/example', function () {
    return response()->json(['message' => 'Hello, world!']);
});
Route::get('/info', [ApiController::class, 'info']);
Route::get('/version', [ApiController::class, 'info']); // Alias para info

// Rutas de autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1'); // Limitación de 5 intentos por minuto por IP
Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/force-password-change', [AuthController::class, 'forcePasswordChange'])->middleware('auth:sanctum');


/*
|-------------------------------------------------------------------------------------------------------
| Protected Routes (Rutas protegidas)
|-------------------------------------------------------------------------------------------------------
| Estas rutas requieren un token de Sanctum válido.
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // Rutas protegidas por sanctum

    // Ruta para cerrar sesión
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-other-devices', [AuthController::class, 'logoutOtherDevices']);
    Route::post('/verify-email', [AuthController::class, 'markEmailAsVerified']);

    // --- GRUPO DE RUTAS PARA ADMINISTRACIÓN (protegidas por roles) ---
    Route::prefix('admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive']);
    });

    // --- GRUPO DE RUTAS PARA EL PERFIL DEL USUARIO AUTENTICADO ---
    Route::prefix('profile')->group(function () {
        Route::get('/me', [ProfileController::class, 'show']);
        Route::patch('/', [ProfileController::class, 'update']);
        Route::patch('/password', [ProfileController::class, 'updatePassword']);
        Route::get('/dashboard-info', [ProfileController::class, 'dashboardInfo']);
    });

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

    // Rutas para Vacaciones Oficiales
    Route::prefix('vacaciones-oficiales')->group(function () {
        Route::get('/', [VacacionesOficialesController::class, 'index']);
        Route::get('/{id}', [VacacionesOficialesController::class, 'show']);
        Route::post('/', [VacacionesOficialesController::class, 'store']);
        Route::put('/{id}', [VacacionesOficialesController::class, 'update']);
        Route::patch('/{id}', [VacacionesOficialesController::class, 'patch']);
        Route::delete('/{id}', [VacacionesOficialesController::class, 'destroy']);
    });

    // Rutas de Asistencias
    Route::prefix('asistencias')->group(function () {
        Route::get('/', [AsistenciaController::class, 'index']);
        Route::post('/', [AsistenciaController::class, 'store']);
        Route::get('/{id}', [AsistenciaController::class, 'show']);
        Route::put('/{id}', [AsistenciaController::class, 'update']);
        Route::delete('/{id}', [AsistenciaController::class, 'destroy']);
        Route::get('/por-empleado/{id}', [AsistenciaController::class, 'porEmpleado']);
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

    // Rutas de Empleados
    Route::prefix('empleados')->group(function () {
        Route::get('/', [EmpleadoController::class, 'index']);
        Route::get('/{id}', [EmpleadoController::class, 'show']);
        Route::get('/{id}/antiguedad', [EmpleadoController::class, 'getAntiguedad']);
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
        Route::get('/exportar', [ReporteEmpleadoController::class, 'exportarReporte']);
    });

    // Rutas de Vacaciones
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
        Route::get('/empleado/{empleadoId}', [VacacionesController::class, 'porEmpleado']);
        Route::get('/estado/{estadoId}', [VacacionesController::class, 'porEstado']);
        Route::get('/periodo/{desde}/{hasta}', [VacacionesController::class, 'porPeriodo']);
        Route::get('/disponibilidad/{empleadoId}', [VacacionesController::class, 'getDisponibilidad']);
        Route::post('/inicializar-historico', [VacacionesController::class, 'inicializarVacacionesHistoricas']);
        Route::get('/disponibilidad', [VacacionesController::class, 'consultarDisponibilidad']);
    });

    // Seccion de Reporte de vaciones
    Route::prefix('reporte-vacaciones')->group(function () {
        Route::get('/empleado-ciclo', [ReporteVacacionesController::class, 'porEmpleadoYCiclo']);
        Route::get('/departamento', [ReporteVacacionesController::class, 'porDepartamento']);
        Route::get('/dias-tomados-mes', [ReporteVacacionesController::class, 'porDiasTomadosPorMes']);
        Route::get('/dias-tomados-semana', [ReporteVacacionesController::class, 'porDiasTomadosPorSemana']);
        Route::get('/periodo', [ReporteVacacionesController::class, 'porPeriodo']);
        Route::get('/resumen', [ReporteVacacionesController::class, 'porResumenVacacionesSolicitadas']);
        Route::get('/top-empleados', [ReporteVacacionesController::class, 'porTopEmpleados']);
    });

    // --- Rutas para el Dashboard de Administración (LayoutAdmin) ---
    Route::prefix('dashboard-admin')->group(function () {
        Route::prefix('asistencias')->group(function () {
            Route::get('/hoy', [AsistenciaController::class, 'getAsistenciasHoy']);
            Route::get('/recientes', [AsistenciaController::class, 'getLatestAsistencias']);
        });
        Route::prefix('empleados')->group(function () {
            Route::get('/status-counts', [EmpleadoController::class, 'getStatusCounts']);
            Route::get('/recien-ingresados', [EmpleadoController::class, 'getRecentlyHired']);
        });
        Route::prefix('vacaciones')->group(function () {
            Route::get('/resumen-estados/{anio}', [VacacionesController::class, 'getResumenEstadosVacaciones']);
            Route::get('/proximas', [VacacionesController::class, 'getProximasVacaciones']);
            Route::get('/dias-por-mes/{anio}', [VacacionesController::class, 'getDiasVacacionesPorMes']);
            Route::get('/empleados/top-antiguos', [VacacionesController::class, 'getTopEmpleadosAntiguos']);
        });
        Route::prefix('users')->group(function () {
            Route::get('/total', [UserController::class, 'getTotalUsersCount']);
            Route::get('/by-role', [UserController::class, 'getUsersCountByRole']);
            Route::get('/active-vs-inactive', [UserController::class, 'getActiveInactiveUsersCount']);
            Route::get('/recently-registered', [UserController::class, 'getRecentlyRegisteredUsers']);
        });
    });

    // --- Rutas para el Dashboard del Empleado (LayoutEmpleado) ---
    Route::prefix('empleado-dashboard/{empleadoId}')->group(function () {
        Route::get('vacaciones/disponibles', [EmployeeDashboardController::class, 'getDiasVacacionesDisponibles']);
        Route::get('asistencias/ultima', [EmployeeDashboardController::class, 'getUltimaAsistencia']);
        Route::get('antiguedad', [EmployeeDashboardController::class, 'getAntiguedad']);
        Route::get('vacaciones/proxima-aprobada', [EmployeeDashboardController::class, 'getProximaVacacionAprobada']);
        Route::get('solicitudes/pendientes', [EmployeeDashboardController::class, 'getSolicitudesPendientes']);
    });

}); // Fin del grupo de middleware 'auth:sanctum'
