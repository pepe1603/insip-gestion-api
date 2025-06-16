<?php

namespace App\Services;

use App\Models\Vacaciones;
use App\Models\Empleado;
use App\Models\EstadoSolicitud;
use App\Models\CicloServicio;
use App\Models\VacacionesOfficiales;
use App\Helpers\ApiResponse;
use App\Exceptions\BusinessException; // Excepción personalizada
use App\Exceptions\EmpleadosExceptions\EmpleadoNoEncontradoException;
use App\Exceptions\VacacionesExceptions\VacacionNoEncontradaException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VacacionesService
{
    // ───────────────────── 1. CRUD BÁSICO ─────────────────────

    public function all()
    {
        return ApiResponse::success(
            Vacaciones::with('empleado', 'estadoSolicitud')->get()
        );
    }

    public function find($id)
    {
        $solicitud = Vacaciones::with('empleado', 'estadoSolicitud')->findOrFail($id); // Usamos findOrFail para lanzar una excepción si no se encuentra
        return ApiResponse::success($solicitud);
    }

    //al crear una solicitud de vacaciones, se calcula la cantidad de días solicitados
    // y se asigna a la propiedad 'dias_vacaciones_totales'.
    //se calcula attrravez de la antigüedad del empleado y se compara cuantos dias le toca por ley de acuerdo a la tabla VacacionesOfficiales
    // Se establece la fecha de solicitud a la fecha actual.
    // Luego, se crea la solicitud de vacaciones en la base de datos.
    // Finalmente, se devuelve una respuesta exitosa con la solicitud creada.
    //se espera que la solicitud sea aceptada o rechazada
    // y que el empleado tenga vacaciones disponibles.
    // Si la solicitud es aceptada, se actualiza el estado de la solicitud
// ... otras partes del servicio ...
  public function registrarSolicitud(array $data)
    {
        // 1. Validar si el empleado existe
        $empleado = Empleado::find($data['empleado_id']);
        if (!$empleado) {
            throw new EmpleadoNoEncontradoException('Empleado no encontrado.', 404);
        }

        if (strtoupper($empleado->estado) !== 'ACTIVO') {
            throw new BusinessException('El empleado debe estar activo para solicitar vacaciones.', 403);
        }

        // Obtener el ciclo de servicio actual para el empleado o crearlo
        // Esta parte es importante porque determinará sobre qué registro de vacaciones trabajaremos.
        $cicloServicioActual = CicloServicio::firstOrCreate([
            'empleado_id' => $empleado->id,
            'anio' => now()->year,
        ]);

        // 2. Calcular los días base de vacaciones para el ciclo actual (por antigüedad)
        $diasBasePorAntiguedad = $this->calcularDiasBasePorAntiguedad($empleado->id);

         // 3. Obtener el registro de Vacaciones para el CICLO ACTUAL.
        // Si no existe, se asumirán 0 días arrastrados para esta solicitud,
        // ya que la asignación inicial de días arrastrados se hará con `inicializarVacacionesEmpleadoHistorico`.
        $vacacionCicloActual = Vacaciones::where('empleado_id', $empleado->id)
                                        ->where('ciclo_servicio_id', $cicloServicioActual->id)
                                        ->first();

        // Los días arrastrados para esta solicitud se toman del registro existente para el ciclo actual.
        // Si el registro aún no existe, o si no se inicializó con `inicializarVacacionesEmpleadoHistorico`,
        // los días arrastrados serán 0 hasta que el primer registro del ciclo se cree (manualmente o por el boot).
        $diasArrastrados = $vacacionCicloActual ? $vacacionCicloActual->dias_vacaciones_arrastrados : 0;

        // 4. El total de días de vacaciones ASIGNADOS para este ciclo
        $vacacionesTotalesAsignadas = $diasBasePorAntiguedad + $diasArrastrados;


        if ($vacacionesTotalesAsignadas <= 0) {
            throw new BusinessException('El empleado no tiene vacaciones disponibles para este ciclo o su antigüedad no le otorga días.', 403);
        }
        // 5. Calcular días solicitados entre las fechas
        $fechaInicio = Carbon::parse($data['fecha_inicio']);
        $fechaFin = Carbon::parse($data['fecha_fin']);
        $diasSolicitados = $this->calcularDiasLaborables($fechaInicio, $fechaFin, $empleado->tipo_contrato);

        // 5.1. Validar traslape de fechas (esta lógica parece correcta y no necesita cambios)
        $traslapo = Vacaciones::where('empleado_id', $empleado->id)
            ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
            ->where(function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
                    ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin])
                    ->orWhere(function ($q2) use ($fechaInicio, $fechaFin) {
                        $q2->where('fecha_inicio', '<=', $fechaInicio)
                            ->where('fecha_fin', '>=', $fechaFin);
                    });
            })
            ->exists();

        if ($traslapo) {
            throw new BusinessException('Ya existe una solicitud de vacaciones aprobada que se cruza con estas fechas.');
        }

        // A. Obtener días de vacaciones usados (de solicitudes aprobadas) para el CICLO ACTUAL
        // Es crucial que esto sume los días disfrutados DENTRO del ciclo actual.
        $diasVacacionesUsados = Vacaciones::where('empleado_id', $empleado->id)
            ->where('ciclo_servicio_id', $cicloServicioActual->id) // Sumar solo para el ciclo actual
            ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
            ->sum('dias_vacaciones_solicitados');


        // B. Calcular días disponibles REALES antes de la solicitud
        // Aquí la `vacacionesTotalesAsignadas` ya incluye los arrastrados.
        $diasDisponiblesActuales = $vacacionesTotalesAsignadas - $diasVacacionesUsados;

        // 6. Verificar si hay suficientes días disponibles para esta nueva solicitud
        if ($diasSolicitados > $diasDisponiblesActuales) {
            throw new BusinessException("No hay suficientes días de vacaciones disponibles. Disponibles: {$diasDisponiblesActuales}, Solicitados: {$diasSolicitados}", 403);
        }

        // 7. Agregar campos calculados a los datos
        $data['dias_vacaciones_totales'] = $vacacionesTotalesAsignadas; // Este ya incluye los arrastrados
        $data['dias_vacaciones_arrastrados'] = $diasArrastrados; // Asignamos el valor que obtuvimos
        $data['dias_vacaciones_solicitados'] = $diasSolicitados;
        $data['dias_vacaciones_disfrutados'] = 0; // Se disfruta cuando se aprueba y se usa (o al final del periodo)
        $data['dias_vacaciones_disponibles'] = $diasDisponiblesActuales - $diasSolicitados; // Los días que quedarán disponibles si esta solicitud fuera aprobada

        // 8. Establecer la fecha de solicitud a la fecha actual y estado inicial (PENDIENTE)
        $data['fecha_solicitud'] = Carbon::now();
        $estadoPendiente = EstadoSolicitud::where('estado', 'PENDIENTE')->firstOrFail();
        $data['estado_solicitud_id'] = $estadoPendiente->id;
        $data['ciclo_servicio_id'] = $cicloServicioActual->id; // Aseguramos que se usa el ID del ciclo actual

        // 9. Guardar la solicitud
        $solicitud = Vacaciones::create($data);

        return ApiResponse::success($solicitud);
    }

    /**
     * Inicializa el registro de vacaciones para un empleado "nuevo en sistema" pero con historial,
     * permitiendo asignar días arrastrados manualmente. Este método crea el *primer* registro
     * de vacaciones para el empleado en el ciclo actual.
     *
     * @param array $data Contiene 'empleado_id' y opcionalmente 'dias_vacaciones_arrastrados'.
     * @return \Illuminate\Http\JsonResponse
     * @throws EmpleadoNoEncontradoException
     * @throws BusinessException Si ya existen vacaciones para el empleado en el ciclo actual.
     */
    public function inicializarVacacionesEmpleadoHistorico(array $data)
    {
        // 1. Validar si el empleado existe
        $empleado = Empleado::find($data['empleado_id']);
        if (!$empleado) {
            throw new EmpleadoNoEncontradoException('Empleado no encontrado.', 404);
        }

        // 2. Encontrar o crear el ciclo de servicio actual para el empleado
        $cicloServicioActual = CicloServicio::firstOrCreate([
            'empleado_id' => $empleado->id,
            'anio' => now()->year,
        ]);

        // 3. Verificar si ya existe un registro de vacaciones para este empleado y ciclo
        $existingVacacion = Vacaciones::where('empleado_id', $empleado->id)
                                        ->where('ciclo_servicio_id', $cicloServicioActual->id)
                                        ->first();

        if ($existingVacacion) {
            throw new BusinessException('Ya existen vacaciones registradas para este empleado en el ciclo actual. Use el método de actualización si necesita modificar días arrastrados existentes.', 409);
        }

        // 4. Determinar los días de vacaciones arrastrados (desde la entrada manual, si existe)
        $diasArrastrados = isset($data['dias_vacaciones_arrastrados']) && is_numeric($data['dias_vacaciones_arrastrados'])
            ? (int) $data['dias_vacaciones_arrastrados']
            : 0;

        // 5. Calcular los días base de vacaciones para el ciclo actual (por antigüedad)
        $diasBasePorAntiguedad = $this->calcularDiasBasePorAntiguedad($empleado->id);

        // 6. Calcular el total de días de vacaciones asignados para el ciclo actual
        $diasVacacionesTotales = $diasBasePorAntiguedad + $diasArrastrados;

        // 7. Obtener el estado inicial (PENDIENTE o un estado específico como 'ASIGNADO_INICIAL')
        // Es recomendable tener un estado claro para estos registros iniciales que no son "solicitudes".
        $estadoInicial = EstadoSolicitud::where('estado', 'PENDIENTE')->firstOrFail();

        // 8. Crear el registro de Vacaciones
        $vacacionInicial = Vacaciones::create([
            'empleado_id' => $empleado->id,
            'ciclo_servicio_id' => $cicloServicioActual->id,
            'dias_vacaciones_totales' => $diasVacacionesTotales,
            'dias_vacaciones_arrastrados' => $diasArrastrados,
            'dias_vacaciones_disfrutados' => 0, // Al inicializar, no ha disfrutado días
            'dias_vacaciones_solicitados' => 0, // No es una solicitud, es una asignación
            'dias_vacaciones_disponibles' => $diasVacacionesTotales, // Al inicio, disponibles = totales
            'fecha_solicitud' => Carbon::now(), // Fecha de cuando se crea este registro inicial
            'fecha_inicio' => null, // No hay fechas de disfrute asociadas a este registro inicial
            'fecha_fin' => null,
            'estado_solicitud_id' => $estadoInicial->id,
            'observaciones' => 'Registro inicial de vacaciones para el ciclo actual, incluyendo días arrastrados de historial manual.',
        ]);

        return ApiResponse::success($vacacionInicial);
    }



    private function calcularDiasLaborables(Carbon $inicio, Carbon $fin, string $tipoContrato): int
    {
        $dias = 0;
        $current = $inicio->copy();

        while ($current->lte($fin)) {
            $diaSemana = $current->dayOfWeek; // 0 = domingo, 6 = sábado

            if ($tipoContrato === 'TIEMPO_COMPLETO') {
                $dias++; // cuenta todos los días
            } else {
                if ($diaSemana >= 1 && $diaSemana <= 5) {
                    $dias++; // solo lunes a viernes
                }
            }

            $current->addDay();
        }

        return $dias;
    }

    public function update($id, array $data)
    {
        $solicitud = Vacaciones::findOrFail($id);

        if (strtoupper($solicitud->estadoSolicitud->estado) === 'APROBADO') {
            throw new BusinessException('No se puede editar una solicitud aprobada.', 403);
        }

        $fechaInicio = Carbon::parse($data['fecha_inicio']);
        $fechaFin = Carbon::parse($data['fecha_fin']);
        $diasSolicitados = $this->calcularDiasLaborables($fechaInicio, $fechaFin, $solicitud->empleado->tipo_contrato);

        $solicitud->update(array_merge($data, [
            'dias_vacaciones_solicitados' => $diasSolicitados
        ]));

        return ApiResponse::success($solicitud->fresh());
    }


    /**
     * Si quieres ir más allá, podríamos:
     * ✨ Mostrar explícitamente qué días se omiten en la interfaz.
     * 🔒 Evitar duplicadas para el mismo periodo de un empleado.
     * 🧠 Añadir lógica para días festivos, si tu empresa tiene un calendario oficial.
     * ¿Te gustaría alguna de esas mejoras o con esto estamos listos por ahora?
     *
     */


    public function delete($id)
    {
        $solicitud = Vacaciones::findOrFail($id); // Usamos findOrFail para lanzar una excepción si no se encuentra

        if (strtoupper($solicitud->estadoSolicitud->estado) === 'APROBADO') {
            throw new BusinessException('No se puede eliminar una solicitud aprobada.', 403);
        }

        $solicitud->delete();
        return ApiResponse::success(['message' => 'Solicitud eliminada correctamente.']);
    }

    // ─────────────── 2. CAMBIO DE ESTADO ───────────────

    public function aprobarSolicitud($id)   { return $this->cambiarEstado($id, 'APROBADO'); }
    public function rechazarSolicitud($id)  { return $this->cambiarEstado($id, 'RECHAZADO'); }
    public function cancelarSolicitud($id)  { return $this->cambiarEstado($id, 'CANCELADO'); }

    /**
     * Cambia el estado de la solicitud de vacaciones.
     *
     * @param int $id
     * @param string $estadoNombre
     * @throws BusinessException
     * @return \Illuminate\Http\Response
     */
    public function cambiarEstado($id, string $estadoNombre)
    {
        $solicitud = Vacaciones::findOrFail($id); // Usamos findOrFail para lanzar una excepción si no se encuentra

        // Verificar que el estado no sea 'APROBADO' si ya fue aprobado.
        if (strtoupper($estadoNombre) === 'CANCELADO' && strtoupper($solicitud->estadoSolicitud->estado) === 'APROBADO') {
            throw new BusinessException('No se puede cancelar una solicitud aprobada.');
        }

        if (strtoupper($estadoNombre) === 'RECHAZADO' && strtoupper($solicitud->estadoSolicitud->estado) === 'APROBADO') {
            throw new BusinessException('No se puede rechazar una solicitud ya aprobada.');
        }

        // Obtener el estado
        $estado = EstadoSolicitud::where('estado', strtoupper($estadoNombre))->firstOrFail(); // Usamos firstOrFail para lanzar una excepción si no se encuentra

        // Actualizar el estado de la solicitud
        $solicitud->update([
            'estado_solicitud_id' => $estado->id,
            'fecha_aprobacion' => now()
        ]);

        return ApiResponse::success($solicitud->fresh());
    }

    // ───────────────────── 3. FILTROS ─────────────────────

    public function getByEmpleado($empleadoId)
    {
        //añadimos datos dedl empleado y estado de solicitud y ademas su datos del empleado
        $empleado = Empleado::find($empleadoId);
        if (!$empleado) {
            throw new EmpleadoNoEncontradoException('Empleado no encontrado.', 404);
        }

        //exite vacione con empleado_id
        if (!Vacaciones::where('empleado_id', $empleadoId)->exists()) {
            throw new VacacionNoEncontradaException('No se encontraron vacaciones para este empleado.', 404);
        }

        $data = Vacaciones::with('estadoSolicitud')
                ->where('empleado_id', $empleadoId)
                ->get();


        return ApiResponse::success( $data );
    }

    public function getPorEstado($estadoId)
    {
        return ApiResponse::success(
            Vacaciones::where('estado_solicitud_id', $estadoId)->get()
        );
    }

    public function getPendientes()
    {
        // Verificar si el estado "PENDIENTE" realmente existe antes de continuar
        $estado = EstadoSolicitud::where('estado', 'PENDIENTE')->first();

        if (!$estado) {
            //lanzar una excepción si no se encuentra el estado
            throw new EstadoSolicitudNoEncontradoException('El estado PENDIENTE no fue encontrado en estados_solicitud.', 404);
        }

        // Convertir el ID a número explícitamente
        $estadoId = (int) $estado->id;

        // Log para verificar que la consulta fue exitosa
        \Log::info('Obteniendo solicitudes de vacaciones pendientes con estado ID: ' . $estadoId);

        // Obtener solicitudes pendientes con información adicional
        $data = Vacaciones::with('empleado', 'estadoSolicitud')
            ->where('estado_solicitud_id', $estadoId)
            ->get();

        // Manejar el caso en el que no haya solicitudes pendientes
        if ($data->isEmpty()) {
            $data = [
                'message' => 'No hay solicitudes de vacaciones pendientes.'
            ];
        }

        return ApiResponse::success($data);
    }

/**
     * Obtiene el reporte de vacaciones por periodo.
     */
    public function getPorPeriodo(string $desde, string $hasta): array
    {


        // Validar las fechas de entrada no son nulas o vacías
        if (empty($desde) || empty($hasta)) {
            throw new BusinessException('Las fechas de inicio y fin no pueden estar vacías.');
        }



        // Validar las fechas de entrada
        if (!Carbon::hasFormat($desde, 'Y-m-d') || !Carbon::hasFormat($hasta, 'Y-m-d')) {
            throw new BusinessException('Las fechas deben estar en formato Y-m-d.');
        }


        $desde = Carbon::parse($desde)->startOfDay();
        $hasta = Carbon::parse($hasta)->endOfDay();
        // Verificar que la fecha de inicio no sea posterior a la fecha de fin
        if ($desde->isAfter($hasta)) {
            throw new BusinessException('La fecha de inicio no puede ser posterior a la fecha de fin.');
        }

        //este metodo retorna ddatos de vacaiones de inifo y fn de las vcacaiones no de unr rgnoen las que fueron creadas
        $vacaciones = Vacaciones::whereBetween('fecha_inicio', [$desde, $hasta])
            ->with(['empleado', 'estadoSolicitud', 'cicloServicio'])
            ->get();

        return $vacaciones->toArray();
    }

    // ─────────────── 4. UTILIDADES Y DISPONIBILIDAD ───────────────

 // Renombramos calcularVacacionesTotales a calcularDiasBasePorAntiguedad
    // para mayor claridad, ya que ahora el "total" es la suma de base + arrastrados.
    public function calcularDiasBasePorAntiguedad($empleadoId)
    {
        $empleado = Empleado::find($empleadoId);
        if (!$empleado) return 0;

        $antiguedad = Carbon::parse($empleado->fecha_ingreso)->diffInYears(now());

        $registro = VacacionesOfficiales::where('tiempo_servicio', '<=', $antiguedad)
            ->orderByDesc('tiempo_servicio')->first();

        return $registro->dias_vacaciones ?? 0;
    }

    // Actualizamos getDisponibilidad para considerar los días arrastrados
    public function getDisponibilidad($empleadoId)
    {
        $empleado = Empleado::findOrFail($empleadoId);

        // Obtener el ciclo de servicio actual del empleado
        $cicloServicioActual = CicloServicio::where('empleado_id', $empleadoId)
                                            ->where('anio', now()->year)
                                            ->first();

        $diasBase = $this->calcularDiasBasePorAntiguedad($empleadoId);
        $diasArrastrados = 0;
        $diasUsados = 0;

        if ($cicloServicioActual) {
            // Obtener el registro de Vacaciones (el primero) para el ciclo actual
            $vacacionCicloActual = Vacaciones::where('empleado_id', $empleadoId)
                                            ->where('ciclo_servicio_id', $cicloServicioActual->id)
                                            ->first(); // Solo el primer registro que lleva los arrastrados

            if ($vacacionCicloActual) {
                $diasArrastrados = $vacacionCicloActual->dias_vacaciones_arrastrados;
            }

            // Sumar los días solicitados aprobados para el ciclo actual
            $diasUsados = Vacaciones::where('empleado_id', $empleadoId)
                                    ->where('ciclo_servicio_id', $cicloServicioActual->id)
                                    ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
                                    ->sum('dias_vacaciones_solicitados');
        }

        // El total asignado ahora es la suma de los días base y los días arrastrados
        $totalAsignado = $diasBase + $diasArrastrados;
        $disponible = $totalAsignado - $diasUsados;


        return ApiResponse::success([
            'empleado' => $empleado->getFullName(),
            'total_base_por_antiguedad' => $diasBase, // Puedes mostrar esto para claridad
            'dias_arrastrados' => $diasArrastrados, // Mostrar el nuevo campo
            'total_asignado' => $totalAsignado,
            'usado' => $diasUsados,
            'disponible' => $disponible,
        ]);
    }

/**
     * Obtiene las vacaciones aprobadas por empleado y ciclo, cargando sus relaciones.
     *
     * @param int $empleadoId
     * @param string $ciclo Puede ser un año o un identificador del ciclo.
     * @return array
     */
    public function getVacacionesAprobadasPorEmpleadoYCiclo(int $empleadoId, string $ciclo): array
    {
        // Asumiendo que 'ciclo' corresponde al 'nombre_ciclo' en la tabla 'ciclo_servicios'
        // o al año si tu lógica de ciclo lo maneja así.
        // Si 'ciclo' es un año (ej. "2024"), podrías buscar por el año en la relación.
        // Aquí usaré la relación con 'cicloServicio' y su columna 'nombre_ciclo'.
        // Si tu columna es diferente, ajusta 'nombre_ciclo'.

        $data = Vacaciones::where('empleado_id', $empleadoId)
            // Aseguramos que solo tomamos las solicitudes APROBADAS
            ->whereHas('estadoSolicitud', function ($query) {
                $query->whereIn('estado', ['APROBADO', 'PENDIENTE']);//cambiar a pendientes----------------------------------------------------
            })
            // Filtramos por el ciclo de servicio. Necesitas que 'ciclo_servicio' sea una relación
            // y que el valor de $ciclo corresponda a una columna en esa tabla (ej. 'nombre_ciclo').
            ->whereHas('cicloServicio', function ($query) use ($ciclo) {
                $query->where('anio', $ciclo);
            })
            // ¡Importante! Cargar las relaciones necesarias para la vista Blade
            ->with(['empleado', 'estadoSolicitud', 'cicloServicio'])
            ->get();

           // dd($data); // Para depurar y ver los datos obtenidos

        return $data->toArray(); // Aseguramos que la respuesta sea un array para la vista
    }


    // ───────────────────── 5. REPORTES ─────────────────────

    public function reporteResumen()
    {
        try {
            // Obtener los datos
            $total = Vacaciones::count();
            $aprobadas = Vacaciones::whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))->count();
            $rechazadas = Vacaciones::whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'RECHAZADO'))->count();
            $pendientes = Vacaciones::whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'PENDIENTE'))->count();

            // Comprobar que los datos son válidos y están en el formato correcto
            $data = [
                [
                    'total' => $total,
                    'aprobadas' => $aprobadas,
                    'rechazadas' => $rechazadas,
                    'pendientes' => $pendientes
                ]
            ];

            // Validación: Verificar que $data sea un array indexado
            if (!is_array($data) || empty($data) || !isset($data[0]) || !is_array($data[0])) {
                throw new \Exception('Los datos generados no tienen el formato esperado.');
            }

            return $data;  // Retornar los datos si todo está correcto

        } catch (\Exception $e) {
            // Lanza la excepción para que se maneje en el controlador
            throw new \Exception('Error en la generación del reporte: ' . $e->getMessage());
        }
    }


    public function reporteTopEmpleados($limit = 5)
    {
        $top = Vacaciones::select('empleado_id', DB::raw('SUM(dias_vacaciones_solicitados) as total_dias'))
            ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
            ->groupBy('empleado_id')
            ->orderByDesc('total_dias')
            ->with('empleado')
            ->limit($limit)
            ->get();


        return $top->toArray();
    }

/**
     * Reporte de vacaciones por departamento.
     * Asegúrate de que el modelo Empleado tenga la relación 'departamento'.
     */
    public function reportePorDepartamento(int $departamentoId)
    {
        $vacaciones = Vacaciones::with([
                'empleado.departamento', // Carga el empleado y su departamento
                'estadoSolicitud',
                'cicloServicio'
            ])
            ->whereHas('empleado', fn($q) => $q->where('departamento_id', $departamentoId))
            ->get();

        return $vacaciones->toArray();
    }

    public function reporteDiasTomadosPorMes($año)
    {
        $datos = Vacaciones::selectRaw('MONTH(fecha_inicio) as mes, SUM(dias_vacaciones_solicitados) as total')
            ->whereYear('fecha_inicio', $año)
            ->whereHas('estadoSolicitud', fn($q) => $q->whereIn('estado', ['APROBADO']))
            ->groupByRaw('MONTH(fecha_inicio)')
            ->orderByRaw('MONTH(fecha_inicio)')
            ->get();

        return $datos->toArray();
    }

    // Este método genera un reporte de los días tomados por semana
    // en un año específico. Se utiliza la función WEEK para agrupar
    // los días por semana y se suman los días de vacaciones solicitados.
    // El resultado se ordena por semana.
    // Se espera que el año sea un entero válido.
    // El método devuelve una respuesta exitosa con los datos obtenidos.
    // Si no se encuentra ningún dato, se devuelve un mensaje de error.
    public function reporteDiasPorSemana($año)
    {
        $datos = Vacaciones::selectRaw('DATEPART(week, fecha_inicio) as semana, SUM(dias_vacaciones_solicitados) as total')
        ->whereYear('fecha_inicio', $año)
        ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
        ->groupBy(DB::raw('DATEPART(week, fecha_inicio)')) // Agrupar por semana usando DATEPART
        ->orderBy('semana')
        ->get();

        return $datos->toArray();
    }


}
