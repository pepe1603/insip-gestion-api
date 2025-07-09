<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;
use Carbon\Carbon;
use App\Models\Empleado;
use App\Models\Vacaciones;
use App\Helpers\ApiResponse;
use App\Models\Departamento;
use App\Models\CicloServicio;
use App\Models\EstadoSolicitud;
use Illuminate\Support\Facades\DB;
use App\Models\VacacionesOfficiales;
use Illuminate\Foundation\Auth\User;
use App\Exceptions\BusinessException;
use Illuminate\Notifications\Notification;
use App\Notifications\NotificarEmpleadoEstadoSolicitud;
use App\Notifications\NotificarEmpleadoSolicitudEnviada;
use App\Exceptions\EmpleadosExceptions\EmpleadoNoEncontradoException;
use App\Exceptions\VacacionesExceptions\VacacionNoEncontradaException;

class VacacionesService
{
    //correo srElixzabeth
    protected $emailAdminRH;

    public function __construct() {
        $this->emailAdminRH = env('MAIL_ADMIN_RH');
    }



    public function all()
    {
        return ApiResponse::success(
            Vacaciones::with('empleado', 'estadoSolicitud', 'cicloServicio:id,anio')->get()
        );
    }

    public function find($id)
    {
        //retonamos la salicitud con sus relaciones y el ciclo de servicio inluido el nombre
        $solicitud = Vacaciones::with('empleado', 'estadoSolicitud', 'cicloServicio.anio')->findOrFail($id);
        return ApiResponse::success($solicitud);
    }

    public function registrarSolicitud(array $data)
    {
        $empleado = Empleado::find($data['empleado_id']);
        if (!$empleado) {
            throw new EmpleadoNoEncontradoException('Empleado no encontrado.', 404);
        }

        //verificar si el empleado esta activo20
        if (!$empleado->status || $empleado->status !== 'ACTIVO') {
            throw new BusinessException("El empleado con id { $empleado->id } se encuentra en estado {$empleado->status}. No se pueden registrar solicitudes de vacaciones para empleados inactivos.", 403);
        }
        //verificar si el empleado tiene un contrato activo
        if (!$empleado->tipo_contrato) {
            throw new BusinessException("El empleado con id { $empleado->id } no tiene un tipo de contrato definido. No se pueden registrar solicitudes de vacaciones sin un tipo de contrato.", 403);

        }

        //nueva validacion
        //--- NUEVA VALIDACION DE SOLICITUDES PENDIENTES ---
        $solicitudPendiente = Vacaciones::where('empleado_id', $empleado->id)
            ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'PENDIENTE'))
            ->exists();

        if ($solicitudPendiente) {
            throw new BusinessException('El empleado ya tiene una solicitud de vacaciones pendiente. No se puede crear una nueva solicitud hasta que la anterior sea procesada.', 403);
        }

        $cicloServicioActual = CicloServicio::firstOrCreate([
            'empleado_id' => $empleado->id,
            'anio' => now()->year,
        ]);

        $vacacionAsignadaCicloActual = Vacaciones::where('empleado_id', $empleado->id)
                                                ->where('ciclo_servicio_id', $cicloServicioActual->id)
                                                ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'ASIGNADO'))
                                                ->first();

        if (!$vacacionAsignadaCicloActual) {
           // throw new BusinessException('El empleado no tiene vacaciones asignadas para el ciclo actual. Por favor, asegúrese de que se haya procesado su aniversario o inicializado sus vacaciones.', 403);
            //sele asiganación de vacaciones para el ciclo actual
            $this->inicializarVacacionesEmpleadoHistorico(['empleado_id' => $empleado->id]);

        }

        $vacacionesTotalesAsignadas = $vacacionAsignadaCicloActual->dias_vacaciones_totales;

        $fechaInicio = Carbon::parse($data['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::parse($data['fecha_fin'])->startOfDay();


        // Verificar si el empleado ha seleccionado las fechas de inicio y fin del dia de hoy, mandar una exception de negocios diciendo que las solicitudes de vacaciones deben ser para fechas futuras.
        if ($fechaInicio->isToday() || $fechaFin->isToday()) {
            throw new BusinessException('Las solicitudes de vacaciones deben ser para fechas futuras. No se pueden solicitar vacaciones para el día de hoy.', 400);
        }

        if ($fechaInicio->isAfter($fechaFin)) {
            throw new BusinessException('La fecha de inicio no puede ser posterior a la fecha de fin.', 400);
        }

        $diasSolicitados = $this->calcularDiasLaborables($fechaInicio, $fechaFin, $empleado->tipo_contrato);

        if ($diasSolicitados <= 0) {
            throw new BusinessException('El período de vacaciones debe ser de al menos un día laborable.', 400);
        }

        // --- INICIO: NUEVA VALIDACIÓN DE MÁXIMO DE UNA SEMANA ---
        // Se considera una semana como máximo 7 días, incluyendo fines de semana para contratos de tiempo completo.
        // La función calcularDiasLaborables ya maneja la lógica de días hábiles vs calendario.
        $MAX_DIAS_VACACIONES = 30;
        if ($diasSolicitados > $MAX_DIAS_VACACIONES) {
            throw new BusinessException("No se pueden solicitar más de {$MAX_DIAS_VACACIONES} días de vacaciones. Por favor, ajuste el período solicitado.", 400);
        }
        // --- FIN: NUEVA VALIDACIÓN DE MÁXIMO DE UNA SEMANA ---


        $traslapo = Vacaciones::where('empleado_id', $empleado->id)
            ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->where(function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereDate('fecha_inicio', '<=', $fechaFin->toDateString())
                      ->whereDate('fecha_fin', '>=', $fechaInicio->toDateString());
                });
            })
            ->exists();

        if ($traslapo) {
            throw new BusinessException('Ya existe una solicitud de vacaciones aprobada que se cruza con estas fechas.');
        }

        $diasVacacionesUsados = Vacaciones::where('empleado_id', $empleado->id)
            ->where('ciclo_servicio_id', $cicloServicioActual->id)
            ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
            ->sum('dias_vacaciones_solicitados');

        $diasDisponiblesActuales = $vacacionesTotalesAsignadas - $diasVacacionesUsados;

        if ($diasSolicitados > $diasDisponiblesActuales) {
            throw new BusinessException("No hay suficientes días de vacaciones disponibles. Disponibles: {$diasDisponiblesActuales}, Solicitados: {$diasSolicitados}", 403);
        }

        $dataToCreate = [
            'empleado_id'               => $empleado->id,
            'ciclo_servicio_id'         => $cicloServicioActual->id,
            'dias_vacaciones_totales'   => $vacacionesTotalesAsignadas,
            'dias_vacaciones_arrastrados' => 0,
            'dias_vacaciones_disfrutados' => 0,
            'dias_vacaciones_solicitados' => $diasSolicitados,
            'dias_vacaciones_disponibles' => $diasDisponiblesActuales - $diasSolicitados,
            'fecha_solicitud'           => Carbon::now()->startOfDay(),
            'fecha_inicio'              => $fechaInicio,
            'fecha_fin'                 => $fechaFin,
            'observaciones'             => $data['observaciones'] ?? null,
        ];

        $estadoPendiente = EstadoSolicitud::where('estado', 'PENDIENTE')->firstOrFail();
        $dataToCreate['estado_solicitud_id'] = $estadoPendiente->id;

        $solicitud = Vacaciones::create($dataToCreate);

                // Notificar al empleado que su solicitud ha sido enviada
        // Validar si el empleado tiene un correo electrónico antes de notificar
        if ($empleado->email) { // Assuming your Empleado model has an 'email' field
            try {
                $empleado->notify(new NotificarEmpleadoSolicitudEnviada($solicitud));
                //Log::info("Notificación de solicitud de vacaciones enviada al empleado: {$empleado->email}");
            } catch (\Throwable $e) {
                //Log::error("Error al enviar notificación al empleado {$empleado->id}: {$e->getMessage()}");
                // Optionally, you could throw a BusinessException here
                // throw new BusinessException("No se pudo enviar la notificación por correo electrónico al empleado.");
            }
        } else {
           // Log::warning("El empleado {$empleado->id} no tiene un correo electrónico configurado. No se envió la notificación de solicitud de vacaciones.");
            // You might want to add a message to the API response here
            // or handle this scenario in the frontend if needed.
        }

        // Notificar a Elizabeth (Admin RH)
        $admin = User::where('email', $this->emailAdminRH)->first(); // cambia el correo real
        if ($admin) {
            //Log::info("Envio de email a admin RH  new ERquest Vacation.");
            $admin->notify(new NotificarAdminSolicitudVacaciones($empleado));
        }

        return ApiResponse::success($solicitud);
    }

    public function inicializarVacacionesEmpleadoHistorico(array $data)
    {
        $empleado = Empleado::find($data['empleado_id']);
        if (!$empleado) {
            throw new EmpleadoNoEncontradoException('Empleado no encontrado.', 404);
        }

        $cicloServicioActual = CicloServicio::firstOrCreate([
            'empleado_id' => $empleado->id,
            'anio' => now()->year,
        ]);

        $existingVacacion = Vacaciones::where('empleado_id', $empleado->id)
                                         ->where('ciclo_servicio_id', $cicloServicioActual->id)
                                         ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'ASIGNADO'))
                                         ->first();

        if ($existingVacacion) {
            throw new BusinessException('Ya existe un registro de asignación de vacaciones para este empleado en el ciclo actual. Use el método de actualización si necesita modificar días arrastrados existentes.', 409);
        }

        $diasArrastrados = isset($data['dias_vacaciones_arrastrados']) && is_numeric($data['dias_vacaciones_arrastrados'])
            ? (int) $data['dias_vacaciones_arrastrados']
            : 0;

        $diasArrastrados = max(0, $diasArrastrados);

        $diasBasePorAntiguedad = $this->calcularDiasBasePorAntiguedad($empleado->id);

        $diasVacacionesTotales = $diasBasePorAntiguedad + $diasArrastrados;

        $estadoAsignado = EstadoSolicitud::where('estado', 'ASIGNADO')->firstOrFail();

        $vacacionInicial = Vacaciones::create([
            'empleado_id'               => $empleado->id,
            'ciclo_servicio_id'         => $cicloServicioActual->id,
            'dias_vacaciones_totales'   => $diasVacacionesTotales,
            'dias_vacaciones_arrastrados' => $diasArrastrados,
            'dias_vacaciones_disfrutados' => 0,
            'dias_vacaciones_solicitados' => 0,
            'dias_vacaciones_disponibles' => $diasVacacionesTotales,
            'fecha_solicitud'           => Carbon::now()->startOfDay(),
            'fecha_inicio'              => null,
            'fecha_fin'                 => null,
            'estado_solicitud_id'       => $estadoAsignado->id,
            'observaciones'             => 'Registro inicial de vacaciones para el ciclo actual, incluyendo días arrastrados de historial manual.',
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
                $dias++;
            } else {
                if ($diaSemana >= 1 && $diaSemana <= 5) {
                    $dias++;
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
        // Se añade verificación para estados CANCELADO y RECHAZADO, ya que no deberían ser editables
        if (strtoupper($solicitud->estadoSolicitud->estado) === 'CANCELADO' || strtoupper($solicitud->estadoSolicitud->estado) === 'RECHAZADO') {
            throw new BusinessException('No se puede editar una solicitud que ha sido cancelada o rechazada.', 403);
        }

        // Si se están actualizando las fechas, recalcular los días solicitados
        if (isset($data['fecha_inicio']) || isset($data['fecha_fin'])) {
            $fechaInicio = Carbon::parse($data['fecha_inicio'] ?? $solicitud->fecha_inicio)->startOfDay();
            $fechaFin = Carbon::parse($data['fecha_fin'] ?? $solicitud->fecha_fin)->startOfDay();

            if ($fechaInicio->isAfter($fechaFin)) {
                throw new BusinessException('La fecha de inicio no puede ser posterior a la fecha de fin.', 400);
            }

            $diasSolicitados = $this->calcularDiasLaborables($fechaInicio, $fechaFin, $solicitud->empleado->tipo_contrato);
            $data['dias_vacaciones_solicitados'] = $diasSolicitados;
            $data['fecha_inicio'] = $fechaInicio;
            $data['fecha_fin'] = $fechaFin;
            // --- INICIO: VALIDACIÓN DE MÁXIMO DE UNA SEMANA EN UPDATE ---
            // Asegurarse de que al actualizar, la duración siga siendo <= 7 días
            $MAX_DIAS_VACACIONES = 7;
            if ($diasSolicitados > $MAX_DIAS_VACACIONES) {
                throw new BusinessException("No se pueden solicitar más de {$MAX_DIAS_VACACIONES} días de vacaciones. Por favor, ajuste el período solicitado.", 400);
            }
            // --- FIN: VALIDACIÓN DE MÁXIMO DE UNA SEMANA EN UPDATE ---
        }

        unset($data['dias_vacaciones_totales']);
        unset($data['dias_vacaciones_arrastrados']);
        unset($data['dias_vacaciones_disfrutados']);
        unset($data['dias_vacaciones_disponibles']);
        unset($data['estado_solicitud_id']); // El estado se cambia con cambiarEstado()

        $solicitud->update($data);

        return ApiResponse::success($solicitud->fresh());
    }

    public function delete($id)
    {
        try{
            $solicitud = Vacaciones::findOrFail($id);

            // No permitir eliminar si está APROBADO, PENDIENTE
            if (in_array(strtoupper($solicitud->estadoSolicitud->estado), ['APROBADO', 'PENDIENTE'])) {
                throw new BusinessException('No se puede eliminar una solicitud que ya ha sido procesada (Aprobada, Pendiente).', 403);
            }

            $solicitud->delete();
            return ApiResponse::success(['message' => 'Solicitud eliminada correctamente.']);
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage());
        } catch (Throwable $e) {
            //\Log::error("Error al actualizar perfil de usuario {$user->id}: " . $e->getMessage(), ['exception' => $e]);
            return ApiResponse::serverError($e->getMessage());
        }
    }


    /**
     * Consulta la disponibilidad de vacaciones para un período, departamento y tipo de contrato.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function consultarDisponibilidad(Request $request)
    {
        try {
            $request->validate([
                'fecha_inicio'      => 'required|date_format:Y-m-d',
                'fecha_fin'         => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
                'departamento_id'   => 'required|integer|exists:departamentos,id',
                'tipo_contrato'     => ['required', 'string', Rule::in(['TIEMPO_COMPLETO', 'MEDIO_TIEMPO'])], // Ajusta según tus tipos
                'limite_empleados_por_dia' => 'sometimes|integer|min:1', // Opcional, por defecto 5
            ]);

            $fechaInicio = $request->input('fecha_inicio');
            $fechaFin = $request->input('fecha_fin');
            $departamentoId = $request->input('departamento_id');
            $tipoContrato = $request->input('tipo_contrato');
            $limiteEmpleadosPorDia = $request->input('limite_empleados_por_dia', 5); // Por defecto 5

            $data = $this->vacacionesService->consultarDisponibilidadVacaciones(
                $fechaInicio,
                $fechaFin,
                $departamentoId,
                $tipoContrato,
                $limiteEmpleadosPorDia
            );

            return ApiResponse::success($data, 'Consulta de disponibilidad realizada con éxito.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Errores de validación: ' . $e->getMessage(), 422, $e->errors());
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            \Log::error("Error al consultar disponibilidad de vacaciones: " . $e->getMessage(), ['exception' => $e]);
            return ApiResponse::error('Ocurrió un error inesperado al consultar la disponibilidad.', 500);
        }
    }



    public function aprobarSolicitud($id) { return $this->cambiarEstado($id, 'APROBADO'); }
    public function rechazarSolicitud($id) { return $this->cambiarEstado($id, 'RECHAZADO'); }
    public function cancelarSolicitud($id) { return $this->cambiarEstado($id, 'CANCELADO'); }

    public function cambiarEstado($id, string $estadoNombre)
    {

        $solicitud = Vacaciones::findOrFail($id);

        $estadoActual = strtoupper($solicitud->estadoSolicitud->estado);
        $nuevoEstado = strtoupper($estadoNombre);

        // Si ya está en el estado deseado, no hacer nada.
        if ($estadoActual === $nuevoEstado) {
            return ApiResponse::success($solicitud->fresh(), "La solicitud ya se encuentra en estado {$nuevoEstado}.");
        }

        // Reglas de transición de estado
        switch ($estadoActual) {
            case 'ASIGNADO':
                // 'ASIGNADO' no es una solicitud de vacaciones, es el balance.
                // No debería ser modificable a APROBADO/RECHAZADO/CANCELADO.
                // Si se desea modificar 'dias_vacaciones_arrastrados' o 'dias_vacaciones_totales'
                // de un registro ASIGNADO, debe ser con un método dedicado para eso.
                throw new BusinessException('El registro de vacaciones "ASIGNADO" no se puede cambiar de estado de esta manera. Representa el balance anual del empleado.', 403);
                break;

            case 'APROBADO':
                // Una solicitud APROBADA no puede cambiar a ningún otro estado.
                throw new BusinessException('Una solicitud APROBADA no puede cambiar de estado.', 403);
                break;

            case 'RECHAZADO':
            case 'CANCELADO':
                // Una solicitud RECHAZADA o CANCELADA no puede cambiar a ningún otro estado.
                throw new BusinessException("Una solicitud {$estadoActual} no puede cambiar de estado.", 403);
                break;

            case 'PENDIENTE':
                // Desde PENDIENTE, se puede pasar a APROBADO, CANCELADO, RECHAZADO.
                if (!in_array($nuevoEstado, ['APROBADO', 'CANCELADO', 'RECHAZADO'])) {
                    throw new BusinessException("El estado 'PENDIENTE' solo puede pasar a 'APROBADO', 'CANCELADO' o 'RECHAZADO'.", 400);
                }
                break;

            default:
                // Cualquier otro estado no previsto
                throw new BusinessException('Transición de estado no permitida para el estado actual.', 400);
        }

        $estadoObj = EstadoSolicitud::where('estado', $nuevoEstado)->firstOrFail();


        try {
            $updateData = [
                'estado_solicitud_id' => $estadoObj->id,
            ];

            // Ajustar los días disfrutados y solicitados de la solicitud específica
            if ($nuevoEstado === 'APROBADO') {
                $updateData['dias_vacaciones_disfrutados'] = $solicitud->dias_vacaciones_solicitados;
                $updateData['fecha_aprobacion'] = Carbon::now()->startOfDay();
            } elseif (in_array($nuevoEstado, ['RECHAZADO', 'CANCELADO'])) {
                // Si se rechaza o cancela una solicitud PENDIENTE, se reinician los días disfrutados y solicitados
                // de este registro de solicitud, ya que no se tomarán.
                $updateData['dias_vacaciones_disfrutados'] = 0;
                $updateData['dias_vacaciones_solicitados'] = 0; // Reinicia los días solicitados para este registro.
                $updateData['fecha_aprobacion'] = null; // Limpiar fecha de aprobación si no se aprueba
            } else {
                $updateData['fecha_aprobacion'] = null; // Default a null si no es aprobado
            }
                $solicitud->update($updateData);

            // Importante: La lógica de actualización del saldo (dias_vacaciones_disponibles)
            // se maneja en el registro de tipo 'ASIGNADO', no en la solicitud individual.
            // Para asegurar que el `getDisponibilidad` siempre sea correcto,
            // no es necesario actualizar el campo `dias_vacaciones_disponibles` en cada solicitud individual.
            // Ese campo es un "snapshot" de la disponibilidad al momento de la creación de la solicitud.
            // La disponibilidad actual siempre se recalcula dinámicamente en `getDisponibilidad`.

                            // Notificar al empleado que su solicitud ha sido enviada
        // Validar si el empleado tiene un correo electrónico antes de notificar
        if ($solicitud->empleado->email) { // Assuming your Empleado model has an 'email' field
            $empleado= $solicitud->empleado;
            try {
                $empleado->notify(new NotificarEmpleadoEstadoSolicitud($nuevoEstado, $solicitud));
                //\Log::info("Notificación de solicitud de vacaciones enviada al empleado: {$empleado->email}");
            } catch (\Throwable $e) {
                //\Log::error("Error al enviar notificación al empleado {$empleado->id}: {$e->getMessage()}");
                // Optionally, you could throw a BusinessException here
                // throw new BusinessException("No se pudo enviar la notificación por correo electrónico al empleado.");
            }
        } else {
           // \Log::warning("El empleado {$empleado->id} no tiene un correo electrónico configurado. No se envió la notificación de solicitud de vacaciones.");
            // You might want to add a message to the API response here
            // or handle this scenario in the frontend if needed.
        }


            return ApiResponse::success($solicitud->fresh(), "Estado de la solicitud cambiado a {$nuevoEstado} correctamente.");

        } catch (BusinessException $e) {
            // Captura tu BusinessException para devolver una respuesta estandarizada.

            return ApiResponse::error( $e->getMessage() );
         } catch (\Exception $e) {

            throw new BusinessException('Error al cambiar el estado de la solicitud: ' . $e->getMessage(), 500);
        }
    }

    // ───────────────────── 3. FILTROS ─────────────────────

    public function getByEmpleado($empleadoId)
    {
        $empleado = Empleado::find($empleadoId);
        if (!$empleado) {
            throw new EmpleadoNoEncontradoException('Empleado no encontrado.', 404);
        }

        $data = Vacaciones::with('estadoSolicitud', 'cicloServicio')
            ->where('empleado_id', $empleadoId)
            ->get();

        return ApiResponse::success($data);
    }

    public function getPorEstado($estadoId)
    {
        return ApiResponse::success(
            Vacaciones::with('empleado', 'estadoSolicitud', 'cicloServicio')
                ->where('estado_solicitud_id', $estadoId)
                ->get()
        );
    }

    public function getPendientes()
    {
        $estado = EstadoSolicitud::where('estado', 'PENDIENTE')->first();

        if (!$estado) {
            throw new BusinessException('El estado PENDIENTE no fue encontrado en estados_solicitud. Asegúrate de que los estados básicos existan en tu base de datos.', 404);
        }

        $data = Vacaciones::with('empleado', 'estadoSolicitud', 'cicloServicio')
            ->where('estado_solicitud_id', $estado->id)
            ->get();

        if ($data->isEmpty()) {
            return ApiResponse::success([], 'No hay solicitudes de vacaciones pendientes.');
        }

        return ApiResponse::success($data);
    }

    public function getPorPeriodo(string $desde, string $hasta): array
    {
        if (empty($desde) || empty($hasta)) {
            throw new BusinessException('Las fechas de inicio y fin no pueden estar vacías.', 400);
        }

        if (!Carbon::hasFormat($desde, 'Y-m-d') || !Carbon::hasFormat($hasta, 'Y-m-d')) {
            throw new BusinessException('Las fechas deben estar en formato Y-m-d.', 400);
        }

        $desde = Carbon::parse($desde)->startOfDay();
        $hasta = Carbon::parse($hasta)->endOfDay();

        if ($desde->isAfter($hasta)) {
            throw new BusinessException('La fecha de inicio no puede ser posterior a la fecha de fin.', 400);
        }

        $vacaciones = Vacaciones::whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
            ->where(function($query) use ($desde, $hasta) {
                $query->whereDate('fecha_inicio', '<=', $hasta->toDateString())
                      ->whereDate('fecha_fin', '>=', $desde->toDateString());
            })
            ->with(['empleado', 'estadoSolicitud', 'cicloServicio'])
            ->get();

        return $vacaciones->toArray();
    }

    // ─────────────────────── 4. UTILIDADES Y DISPONIBILIDAD ───────────────────────

    public function calcularDiasBasePorAntiguedad(int $empleadoId): int
    {
        $empleado = Empleado::find($empleadoId);
        if (!$empleado) {
            throw new EmpleadoNoEncontradoException('Empleado no encontrado al calcular días por antigüedad.', 404);
        }

        $antiguedad = Carbon::parse($empleado->fecha_ingreso)->diffInYears(now());

        $registro = VacacionesOfficiales::where('tiempo_servicio', '<=', $antiguedad)
            ->orderByDesc('tiempo_servicio')->first();

        return $registro->dias_vacaciones ?? 0;
    }

    public function getDiasArrastradosDelCicloAnterior(int $empleadoId, int $anioActual): int
    {
        $anioAnterior = $anioActual - 1;

        $cicloAnterior = CicloServicio::where('empleado_id', $empleadoId)
                                     ->where('anio', $anioAnterior)
                                     ->first();

        $diasDisponiblesAnioAnterior = 0;
        if ($cicloAnterior) {
            $vacacionAsignadaAnioAnterior = Vacaciones::where('empleado_id', $empleadoId)
                                                      ->where('ciclo_servicio_id', $cicloAnterior->id)
                                                      ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'ASIGNADO'))
                                                      ->first();

            if ($vacacionAsignadaAnioAnterior) {
                $diasUsadosAnioAnterior = Vacaciones::where('empleado_id', $empleadoId)
                                                    ->where('ciclo_servicio_id', $cicloAnterior->id)
                                                    ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
                                                    ->sum('dias_vacaciones_solicitados');

                $diasDisponiblesAnioAnterior = ($vacacionAsignadaAnioAnterior->dias_vacaciones_totales) - $diasUsadosAnioAnterior;

                if ($diasDisponiblesAnioAnterior < 0) {
                    $diasDisponiblesAnioAnterior = 0;
                }
            }
        }
        return $diasDisponiblesAnioAnterior;
    }

    public function getDisponibilidad(int $empleadoId)
    {
        $empleado = Empleado::findOrFail($empleadoId);

        $cicloServicioActual = CicloServicio::firstOrCreate([
            'empleado_id' => $empleadoId,
            'anio' => now()->year,
        ]);

        $diasBase = $this->calcularDiasBasePorAntiguedad($empleadoId);
        $diasArrastrados = 0;
        $diasUsados = 0;

        $vacacionAsignadaCicloActual = Vacaciones::where('empleado_id', $empleadoId)
                                                ->where('ciclo_servicio_id', $cicloServicioActual->id)
                                                ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'ASIGNADO'))
                                                ->first();

        if ($vacacionAsignadaCicloActual) {
            $diasArrastrados = $vacacionAsignadaCicloActual->dias_vacaciones_arrastrados;
        }

        $diasUsados = Vacaciones::where('empleado_id', $empleadoId)
                                ->where('ciclo_servicio_id', $cicloServicioActual->id)
                                ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
                                ->sum('dias_vacaciones_solicitados');

        $totalAsignado = $diasBase + $diasArrastrados;
        $disponible = $totalAsignado - $diasUsados;

        return ApiResponse::success([
            'empleado'                  => $empleado->getFullName(),
            'total_base_por_antiguedad' => $diasBase,
            'dias_arrastrados'          => $diasArrastrados,
            'total_asignado'            => $totalAsignado,
            'usado'                     => $diasUsados,
            'disponible'                => $disponible,
        ]);
    }

    public function getVacacionesAprobadasPorEmpleadoYCiclo(int $empleadoId, int $anio): array
    {
        $data = Vacaciones::where('empleado_id', $empleadoId)
            ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
            ->whereHas('cicloServicio', fn($q) => $q->where('anio', $anio))
            ->with(['empleado', 'estadoSolicitud', 'cicloServicio'])
            ->get();

        return $data->toArray();
    }

    // ───────────────────── 5. REPORTES ─────────────────────

    public function reporteResumen()
    {
        $estadoAprobadoId = EstadoSolicitud::where('estado', 'APROBADO')->first()->id ?? null;
        $estadoRechazadoId = EstadoSolicitud::where('estado', 'RECHAZADO')->first()->id ?? null;
        $estadoPendienteId = EstadoSolicitud::where('estado', 'PENDIENTE')->first()->id ?? null;
        $estadoCanceladoId = EstadoSolicitud::where('estado', 'CANCELADO')->first()->id ?? null;
        $estadoAsignadoId = EstadoSolicitud::where('estado', 'ASIGNADO')->first()->id ?? null;

        $counts = [
            'total'          => Vacaciones::count(),
            'aprobadas'      => $estadoAprobadoId ? Vacaciones::where('estado_solicitud_id', $estadoAprobadoId)->count() : 0,
            'rechazadas'     => $estadoRechazadoId ? Vacaciones::where('estado_solicitud_id', $estadoRechazadoId)->count() : 0,
            'pendientes'     => $estadoPendienteId ? Vacaciones::where('estado_solicitud_id', $estadoPendienteId)->count() : 0,
            'canceladas'     => $estadoCanceladoId ? Vacaciones::where('estado_solicitud_id', $estadoCanceladoId)->count() : 0,
            'asignadas'      => $estadoAsignadoId ? Vacaciones::where('estado_solicitud_id', $estadoAsignadoId)->count() : 0,
            'no_solicitadas' => 0
        ];

        return [$counts];
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

    public function reportePorDepartamento(int $departamentoId)
    {
        $vacaciones = Vacaciones::with([
                'empleado.departamento',
                'estadoSolicitud',
                'cicloServicio'
            ])
            ->whereHas('empleado', fn($q) => $q->where('departamento_id', $departamentoId))
            ->get();

        return $vacaciones->toArray();
    }

    public function reporteDiasTomadosPorMes(int $anio)
    {
        $datos = Vacaciones::selectRaw('MONTH(fecha_inicio) as mes, SUM(dias_vacaciones_solicitados) as total')
            ->whereYear('fecha_inicio', $anio)
            ->whereHas('estadoSolicitud', fn($q) => $q->whereIn('estado', ['APROBADO']))
            ->groupByRaw('MONTH(fecha_inicio)')
            ->orderByRaw('MONTH(fecha_inicio)')
            ->get();
        return $datos->toArray();
    }

    public function reporteDiasPorSemana(int $anio)
    {
        $datos = Vacaciones::selectRaw('DATEPART(week, fecha_inicio) as semana, SUM(dias_vacaciones_solicitados) as total')
            ->whereYear('fecha_inicio', $anio)
            ->whereHas('estadoSolicitud', fn($q) => $q->where('estado', 'APROBADO'))
            ->groupBy(DB::raw('DATEPART(week, fecha_inicio)'))
            ->orderBy('semana')
            ->get();
        return $datos->toArray();
    }
}
