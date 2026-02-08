<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\User; // Importa el modelo User
use App\Notifications\EmployeeAccountCreatedNotification;
use Illuminate\Support\Facades\Log; // Para registrar errores
use Illuminate\Support\Facades\Hash; // Para hashear la contraseña
use Throwable; // Para capturar cualquier otra excepción inesperada
use App\Exceptions\BusinessException; // Importa tu BusinessException
use App\Models\Empleado; // ¡IMPORTANTE! Asegúrate de importar el modelo Empleado
use Illuminate\Validation\ValidationException; // Para manejar errores de validación de Laravel
// use App\Notifications\RegisterUserNotification; // Si decides notificar al empleado

class UserController extends Controller
{
    /**
     * Constructor para aplicar middleware de autenticación.
     * En un sistema real, aquí también aplicarías middlewares de autorización (ej. 'can:manage-users').
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');

        // Primero, aplica el Gate más restrictivo a las acciones críticas (admin solo)
        $this->middleware('can:admin')->only(['store', 'update', 'destroy', 'toggleActive']);

        // Luego, aplica el Gate para supervisores a las acciones de lectura
        $this->middleware('can:supervisor')->only(['index', 'show']);

        // Un admin cumplirá ambos, un supervisor solo el segundo para index/show.
        // Esto es más explícito sobre quién puede hacer qué.
    }

    /**
     * Lista todos los usuarios con opciones de búsqueda, filtrado y paginación. (solo accessible por supervisor y admin)
     * GET /api/admin/users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = User::query();

            // --- Filtros ---
            // Búsqueda por nombre o email
            if ($request->has('search') && $request->search) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm)
                      ->orWhere('email', 'like', $searchTerm);
                });
            }

            // Filtrar por rol (asumiendo una columna 'role' en tu tabla 'users')
            if ($request->has('role') && $request->role) {
                $query->where('role', $request->role);
            }

            // Filtrar por estado activo (asumiendo una columna 'is_active')
            if ($request->has('is_active') && in_array($request->is_active, ['0', '1'])) {
                $query->where('is_active', (int)$request->is_active);
            }

            // --- Ordenamiento ---
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'asc');
            if (in_array($sortBy, ['id', 'name', 'email', 'created_at', 'updated_at']) && in_array($sortOrder, ['asc', 'desc'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // --- Paginación ---
            $perPage = $request->get('per_page', 10);
            $users = $query->paginate($perPage);

            return ApiResponse::success($users);

        } catch (Throwable $e) {
            // Captura cualquier excepción inesperada y registra el error.
            $response = [
                'message' => 'Ocurrió un error inesperado al listar los usuarios.',
                'errors' => [
                    'message' => "Error al listar usuarios: " . $e->getMessage(),
                    'exception' => $e
                ],
            ];
            return ApiResponse::serverError($response);
        }
    }

    /**
     * Muestra los detalles de un usuario específico. (accesible solo por supervisor y admin)
     * GET /api/admin/users/{id}
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                // Lanza tu BusinessException si el usuario no se encuentra.
                // Tu Handler ya debería saber cómo convertir esto a una respuesta 404.
                throw new BusinessException('Usuario no encontrado.', 404);
            }

            return ApiResponse::success( $user);

        } catch (BusinessException $e) {
            // Captura tu BusinessException para devolver una respuesta estandarizada.
            return ApiResponse::send( (int)$e->getCode(), $e->getMessage() );
        } catch (Throwable $e) {
            $response = [
                'message' => 'Ocurrió un error inesperado al obtener los detalles del usuario.',
                'errors' => [
                    'message' => "Error al mostrar usuario {$id}: " . $e->getMessage(),
                    'exception' => $e
                ],
            ];
            return ApiResponse::serverError($response);
        }
    }


    ## Método `store` (Registro por Administrador) con `must_change_password`


    /**
     * Crea un nuevo usuario por parte de un administrador.
     * Solo los usuarios con el rol 'admin' pueden acceder a este método.
     * Permite asociar a un empleado (si se proporciona 'empleado_id', el email del usuario se toma del empleado).
     * El rol es definido libremente por el administrador.
     * Se marca al usuario para que cambie su contraseña en el primer inicio de sesión.
     * Se expone la contraseña temporal en la respuesta para el administrador, pero con advertencia de seguridad.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Almacenamos la contraseña sin hashear temporalmente para devolverla
        // ¡ADVERTENCIA DE SEGURIDAD!: Exponer rawPassword en la respuesta NO es una buena práctica
        // Lo ideal es que esta contraseña solo se envíe por correo electrónico y NO en la respuesta API.
        $rawPassword = $request->input('password');

        $emailForUser = null; // Inicializamos la variable para el email final del usuario
        $empleado = null;

        try {
            // 1. **Validación inicial de los datos de entrada comunes**
            // NOTA: No incluimos 'email' aquí, ya que su requerimiento es condicional.
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'password' => 'required|string|min:8',
                'role' => ['required', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],
                'empleado_id' => 'nullable|integer',
                'is_active' => 'boolean', // Opcional, por defecto true si no se envía
            ]);

            // 2. **Lógica para determinar el email del usuario y validar empleado_id**
            if (isset($validated['empleado_id']) && $validated['empleado_id'] !== null) {
                $empleado = Empleado::find($validated['empleado_id']);

                if (!$empleado) {
                    throw new BusinessException("El ID de empleado proporcionado no existe.", 404);
                }

                // Validar si el empleado está inactivo
                if ($empleado->status === 'INACTIVO') {
                    throw new BusinessException("No se puede registrar un usuario para un empleado que se encuentra Inactivo.", 400);
                }

                // **NUEVA VERIFICACIÓN**: Asegurarse de que el empleado tenga un email
                if (empty($empleado->email)) {
                    throw new BusinessException("El empleado con ID {$validated['empleado_id']} no tiene un correo electrónico asignado. No se puede vincular el usuario sin un email de empleado.", 400);
                }

                // Asignar el email del empleado al usuario
                $emailForUser = $empleado->email;

                // Validar si el empleado ya tiene un usuario asociado
                $existingUserWithEmpleado = User::where('empleado_id', $validated['empleado_id'])->first();
                if ($existingUserWithEmpleado) {
                    throw new BusinessException("El empleado con ID {$validated['empleado_id']} ya está asociado a otro usuario ({$existingUserWithEmpleado->email}).", 400);
                }

                // Si el email del empleado ya está en uso por un usuario 'genérico' (no asociado a un empleado)
                $existingUserWithEmail = User::where('email', $emailForUser)
                                              ->whereNull('empleado_id') // Verifica solo usuarios no asociados a un empleado
                                              ->first();
                if ($existingUserWithEmail) {
                    throw new BusinessException("El correo electrónico del empleado ({$emailForUser}) ya está en uso por otro usuario no asociado a un empleado. Por favor, desvincúlelo primero o use otro empleado.", 400);
                }

            } else {
                // Si NO se proporciona empleado_id, entonces el campo 'email' del request es requerido y debe ser único.
                // Aquí se valida el email del request y se asigna.
                $request->validate(['email' => 'required|string|email|max:255|unique:users,email']);
                $emailForUser = $request->input('email'); // Tomar el email del request
            }

            // 3. **Verificación final de unicidad del email**
            // Esta verificación es crucial para asegurar que el email que se va a usar (ya sea del empleado o del request)
            // no esté ya asignado a un usuario existente.
            // La lógica ya implementada cubre bien casos donde un email de empleado podría estar ya en uso por un usuario 'stand-alone'
            // o si un usuario 'stand-alone' ya tiene el email que se intenta usar para un nuevo usuario genérico.
            // La validación `unique:users,email` en el paso anterior (para usuarios sin empleado_id) y
            // las comprobaciones de `existingUserWithEmail` y `existingUserWithEmpleado` ya manejan la mayoría de los casos.
            // Este bloque final podría simplificarse o ser redundante dependiendo de la cobertura de los anteriores.
            // Si $emailForUser es el email de un empleado, las comprobaciones anteriores ya cubrieron la unicidad.
            // Si $emailForUser viene del request, el 'unique:users,email' ya lo cubre.
            // Se comenta para evitar redundancia, a menos que haya un caso muy específico que no se considere.
            /*
            $existingUserFinalCheck = User::where('email', $emailForUser)->first();
            if ($existingUserFinalCheck) {
                // Si existe un usuario con este email, debemos verificar si es el mismo empleado_id (si aplica)
                // o si estamos intentando asociar un email que ya pertenece a un usuario "stand-alone".
                // Esta lógica ya está cubierta por las validaciones previas de `existingUserWithEmpleado` y `existingUserWithEmail`.
                if ( ($empleado && $existingUserFinalCheck->empleado_id !== $empleado->id) || (!$empleado && $existingUserFinalCheck->empleado_id !== null) ) {
                    throw new BusinessException("El correo electrónico '{$emailForUser}' ya está registrado en el sistema con otro usuario.", 400);
                }
            }
            */


            // 4. **Crear el nuevo usuario**
            $user = User::create([
                'name' => $validated['name'],
                'email' => $emailForUser, // Usamos el email final (del empleado o del request)
                'password' => Hash::make($rawPassword), // Hasheamos la contraseña
                'role' => $validated['role'], // El rol se toma directamente del input del admin
                'is_active' => $validated['is_active'] ?? true, // Por defecto activo
                'empleado_id' => $empleado->id ?? null, // Asignamos el ID del empleado o null
                'must_change_password' => true, // ¡ESTO ES CORRECTO Y CRUCIAL!
            ]);

            // 5. **Notificar al usuario**
            $loginUrl = env('APP_FRONTEND_LOGIN_URL', 'http://localhost:3000/login');
            // Asegúrate de que $user->email sea el correo correcto al que se enviará la notificación.
            // La notificación se enviará al email final del usuario.
            $user->notify(new EmployeeAccountCreatedNotification($user, $loginUrl, $rawPassword, $request->ip()));

            // 6. **Devolver una respuesta exitosa**
            return response()->json([
                'message' => 'Usuario creado exitosamente por el administrador.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'empleado_id' => $user->empleado_id,
                    'must_change_password' => $user->must_change_password, // Ahora sí debería ser 'true'
                    // ¡ADVERTENCIA DE SEGURIDAD CRÍTICA!:
                    // No es recomendable exponer la contraseña sin hashear en la respuesta API.
                    // Si el frontend necesita la contraseña temporal por alguna razón,
                    // considera un flujo donde se envíe SOLO por correo electrónico al usuario o no se exponga.
                    // Si se expone, debe ser con una justificación de seguridad muy fuerte y por un tiempo muy limitado.
                    'temp_password' => $rawPassword,
                ],
                'action_required' => 'El usuario debe cambiar su contraseña al iniciar sesión por primera vez. Se le ha enviado un correo con sus credenciales e instrucciones.'
            ], 201); // Código 201 - Created

        } catch (ValidationException $e) {
            return ApiResponse::send(422, [
                'message' => 'Error de validación al crear el usuario.',
                'errors' => $e->errors()
            ]);
        } catch (BusinessException $e) {
            return ApiResponse::send($e->getCode(), $e->getMessage());
        } catch (Throwable $e) { // Captura cualquier otra excepción inesperada
            Log::error('Error en registro interno de usuario (admin):', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ApiResponse::serverError([
                'message' => 'Error inesperado al crear el usuario.',
                'error' => $e->getMessage(),
            ]);
        }
    }


/**
     * Actualiza los datos de un usuario existente. (accesible solo por admin)
     * PUT /api/admin/users/{id}
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                throw new BusinessException('Usuario a actualizar no encontrado.', 404);
            }

            // --- INICIO: Lógica para evitar que el admin se modifique a sí mismo ---
            $authenticatedUser = $request->user();

            if ($authenticatedUser->id === $user->id) {
                $criticalFieldsChanged = false;
                if ($request->has('role') && $request->input('role') !== $user->role->value) {
                    $criticalFieldsChanged = true;
                }
                if ($request->has('is_active') && $request->boolean('is_active') !== $user->is_active) {
                    $criticalFieldsChanged = true;
                }

                if ($criticalFieldsChanged) {
                    throw new BusinessException('Un administrador no puede modificar su propio rol o estado de actividad. Contacte a otro administrador.', 403);
                }
            }
            // --- FIN: Lógica ---


            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:8|confirmed',
                'role' => ['sometimes', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],
                'is_active' => 'sometimes|boolean',
                'empleado_id' => 'nullable|integer',
            ]);

            // === Lógica para actualizar empleado_id y manejar el email ===
            $originalEmpleadoId = $user->empleado_id;

            // Si se intenta vincular/desvincular un empleado
            if (array_key_exists('empleado_id', $validatedData)) {
                $newEmpleadoId = $validatedData['empleado_id'];

                if ($newEmpleadoId !== null) {
                    $empleado = Empleado::find($newEmpleadoId);
                    if (!$empleado) {
                        throw new BusinessException("El ID de empleado proporcionado no existe.", 404);
                    }
                    if ($empleado->status === 'INACTIVO') {
                        throw new BusinessException("No se puede vincular un usuario a un empleado inactivo.", 400);
                    }
                    // --- INICIO: NUEVA VALIDACIÓN DE EMAIL EN EMPLEADO ---
                    if (empty($empleado->email)) {
                        throw new BusinessException("El empleado con ID {$newEmpleadoId} no tiene un correo electrónico asignado. No se puede vincular el usuario sin un email de empleado.", 400);
                    }
                    // --- FIN: NUEVA VALIDACIÓN ---

                    $existingUserWithEmpleado = User::where('empleado_id', $newEmpleadoId)->where('id', '!=', $user->id)->first();
                    if ($existingUserWithEmpleado) {
                        throw new BusinessException("El empleado con ID {$newEmpleadoId} ya está asociado a otro usuario ({$existingUserWithEmpleado->email}).", 400);
                    }
                    // Si el email del usuario en la petición no coincide con el del empleado, lanzar error
                    if (isset($validatedData['email']) && $validatedData['email'] !== $empleado->email) {
                        throw new BusinessException("Si el usuario está vinculado a un empleado, el email debe coincidir con el del empleado ({$empleado->email}). Por favor, actualice el email del empleado primero.", 400);
                    }
                    // Asigna el email del empleado al usuario
                    $user->email = $empleado->email;
                } else {
                    // Si se desvincula un empleado (empleado_id se vuelve null)
                    if (isset($validatedData['role']) && $validatedData['role'] === UserRole::Employee->value && empty($request->input('email'))) {
                         throw new BusinessException("El email es requerido si se desvincula el empleado y el rol es 'Empleado'.", 422);
                    }
                }
                // Aplica el cambio de empleado_id
                $user->empleado_id = $newEmpleadoId;
            } else {
                // Si 'empleado_id' NO está en la petición (no se intenta vincular/desvincular)
                // Pero el usuario YA tiene un empleado_id asociado
                if ($user->empleado_id !== null && isset($validatedData['email']) && $validatedData['email'] !== $user->empleado->email) {
                    throw new BusinessException("No se puede cambiar el email de un usuario vinculado a un empleado. Actualice el email del empleado (ID: {$user->empleado_id}) primero.", 400);
                }
            }

            // Solo actualiza los campos que fueron enviados en la solicitud, excepto password y empleado_id.
            // Y exceptuando el 'email' si fue sobrescrito por la lógica del empleado_id.
            $fieldsToFill = array_diff_key($validatedData, array_flip(['password', 'empleado_id']));
            if ($user->empleado_id !== null) {
                unset($fieldsToFill['email']);
            }
            $user->fill($fieldsToFill);

            // Si se proporciona una nueva contraseña, la hashea y fuerza el cambio
            if (isset($validatedData['password']) && $validatedData['password']) {
                $user->password = Hash::make($validatedData['password']);
                $user->must_change_password = true;
            }

            $user->save();

            return ApiResponse::success($user);

        } catch (BusinessException $e) {
            return ApiResponse::send($e->getCode(), $e->getMessage());
        } catch (ValidationException $e) {
            return ApiResponse::send(422,[ 'message' => 'Los datos proporcionados no son válidos.', 'errors' => $e->errors()]);
        } catch (Throwable $e) {
            $response = [
                'message' => 'Ocurrió un error inesperado al actualizar el usuario.',
                'errors' => [
                    'message' => "Error al actualizar usuario {$id}: " . $e->getMessage(),
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ];
            return ApiResponse::serverError($response);
        }
    }

    /**
     * Elimina un usuario.(solo accesible por admin)
     * DELETE /api/admin/users/{id}
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                throw new BusinessException('Usuario a eliminar no encontrado.', 404);
            }

            // Opcional: Prevenir que un administrador se elimine a sí mismo
            if ($user->id === auth()->id()) {
                throw new BusinessException('No puedes eliminar tu propia cuenta.', 404);
            }

            //verficar el estado is_active -> true par aoder eliminar.
            if ($user->is_active === true){
                throw new BusinessException('No puedes eliminar un usuario que esta activo.', 400);
            }

            $user->delete();

            return ApiResponse::send(204 , null); // 204 No Content para eliminación exitosa sin cuerpo de respuesta

        } catch (BusinessException $e) {
            return ApiResponse::send($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            $response = [
                'message' => 'Ocurrió un error inesperado al eliminar el usuario.',
                'errors' => [
                    'message' => "Error al eliminar usuario {$id}: " . $e->getMessage(),
                    'exception' => $e
                ],
            ];
            return ApiResponse::serverError($response);
        }
    }

    /**
     * Activa o inactiva un usuario específico.
     * PATCH /api/admin/users/{id}/toggle-active
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleActive(int $id, Request $request)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                throw new BusinessException('Usuario no encontrado.', 404);
            }

            // Opcional: Prevenir que un admin se inactive a sí mismo
            if ($user->id === auth()->id()) {
                throw new BusinessException('No puedes cambiar tu propio estado de actividad.', 403); // Cambiado a 403 Forbidden
            }

            // La lógica para toggling: si está activo, se vuelve inactivo; si está inactivo, se vuelve activo.
            $user->is_active = !$user->is_active;
            $user->save();

            $status = $user->is_active ? 'activo' : 'inactivo';
            return response()->json(
                [
                'message' => "El estado del usuario cambió a {$status} exitosamente.",
                'user' => $user
                ]
            );

        } catch (BusinessException $e) {
            return ApiResponse::send($e->getCode(), $e->getMessage() );
        } catch (Throwable $e) {
            $response = [
                'message' => 'Ocurrió un error inesperado al cambiar el estado del usuario.',
                'errors' => [
                    'message' => "Error al cambiar estado de actividad para usuario {$id}: " . $e->getMessage(),
                    'exception' => $e
                ],
            ];
            return ApiResponse::serverError($response);
        }
    }

    // Métodos para el Dashboard de Usuarios (sin cambios)

    /**
     * Obtiene el conteo total de usuarios.
     * GET /api/dashboard/users/total
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTotalUsersCount()
    {
        try {
            // Asumiendo que 'User' es el modelo para tus usuarios
            $totalUsers = User::count();
            return response()->json(
            ['status'=> 200, 'total_users' => $totalUsers, 'message' => 'Conteo total de usuarios obtenido exitosamente.']);
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener el conteo total de usuarios: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtiene el conteo de usuarios por cada rol.
     * GET /api/dashboard/users/by-role
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsersCountByRole()
    {
        try {
            $usersByRole = User::select('role', DB::raw('count(*) as count'))
                               ->groupBy('role')
                               ->get();

            $formattedData = $usersByRole->mapWithKeys(function ($item) {
                //acedemos al valor de la cadena del ENum
                return [strtolower($item->role->value) => $item->count];
            })->toArray();
            return response()->json(
                ['data' => $formattedData,'message'=> 'Conteo de usuarios por rol obtenido exitosamente.']);
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener el conteo de usuarios por rol: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtiene el conteo de usuarios activos vs inactivos.
     * GET /api/dashboard/users/active-vs-inactive
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveInactiveUsersCount()
    {
        try {
            $activeUsers = User::where('is_active', true)->count();
            $inactiveUsers = User::where('is_active', false)->count();

            return response()->json(
                [
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'message' => 'Conteo de usuarios activos e inactivos obtenido exitosamente.']);
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener el conteo de usuarios activos/inactivos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtiene una lista de usuarios registrados recientemente (ej. en los últimos 30 días).
     * GET /api/dashboard/users/recently-registered
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentlyRegisteredUsers(Request $request)
    {
        try {
            $days = $request->input('days', 30); // Número de días a considerar, por defecto 30
            $limit = $request->input('limit', 5); // Límite de usuarios a retornar, por defecto 5

            $recentUsers = User::where('created_at', '>=', now()->subDays($days))
                               ->orderBy('created_at', 'desc')
                               ->limit($limit)
                               ->get(['id', 'name', 'email', 'role', 'created_at']); // Selecciona solo los campos necesarios

            return response()->json([
                'data' => $recentUsers->toArray(),'message' => 'Usuarios registrados recientemente obtenidos exitosamente.']);
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener usuarios registrados recientemente: ' . $e->getMessage(), 500);
        }
    }
}
