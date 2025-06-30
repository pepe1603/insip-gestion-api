<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\User; // Importa el modelo User
use Throwable; // Para capturar cualquier otra excepción inesperada
use App\Exceptions\BusinessException; // Importa tu BusinessException
use Illuminate\Validation\ValidationException; // Para manejar errores de validación de Laravel

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
     * Muestra los detalles de un usuario específico. (acesible ssolo por supervisor y admin)
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

    /**
     * Crea un nuevo usuario.(Solo accesible por admin)
     * POST /api/admin/users
     *Registro interno de un nuevo usuario por un administrador.
    * Solo los usuarios con el rol 'admin' pueden acceder a este método.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
       try {
            // Validar los datos de entrada
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'role' => ['required', 'string', Rule::in(array_column(UserRole::cases(), 'value'))], // Asegurarse que el rol sea uno de los definidos en el enum
            ]);

            // Verificar si el correo ya está registrado en la base de usuarios
            $existingUser = User::where('email', $validated['email'])->first();
            if ($existingUser) {
                return response()->json([
                    'message' => 'El correo electrónico ya está registrado en el sistema.',
                ], 400); // 400 - Bad Request
            }

            // Crear el nuevo usuario con el rol proporcionado
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'is_active' => true,
            ]);

            // Notificar al usuario de su creación
            $loginUrl = env('APP_FRONTEND_LOGIN_URL', 'http://localhost:3000/login');
            $user->notify(new RegisterUserNotification($user, $loginUrl, $request->ip()));

            return response()->json([
                'message' => 'Usuario registrado exitosamente.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ], 201); // Código 201 - Created

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en registro interno:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error inesperado al registrar el usuario.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualiza los datos de un usuario existente.(accesible solo por admin)
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

            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255', // 'sometimes' valida solo si el campo está presente
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id, // Ignora el email del usuario actual
                'password' => 'nullable|string|min:8|confirmed', // Nullable: la contraseña no es obligatoria para actualizar
                // Validar contra los valores del Enum
                'role' => ['sometimes', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],
                'is_active' => 'sometimes|boolean',
            ]);

            // Solo actualiza los campos que fueron enviados en la solicitud
            $user->fill($validatedData);

            // Si se proporciona una nueva contraseña, la hashea
            if (isset($validatedData['password']) && $validatedData['password']) {
                $user->password = bcrypt($validatedData['password']);
            }

            $user->save();

            return ApiResponse::success($user);

        } catch (BusinessException $e) {
            return ApiResponse::send($e->getCode(), $e->getMessage());
        } catch (ValidationException $e) {
            return ApiResponse::send(422,[ 'message' => 'Los datos proporcionados no son válidos.', 'exceptions' => $e->errors()]);
        } catch (Throwable $e) {
             $response = [
                'message' => 'Ocurrió un error inesperado al actualizar el usuario.',
                'errors' => [
                    'message' => "Error al actualizar usuario {$id}: " . $e->getMessage(),                ],
            ];
            return ApiResponse::serverError($response);
        }
    }

    /**
     * Elimina un usuario.(solo accessible por admin)
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
                throw new BusinessException('No puedes eliminar tu propia cuenta.', 403);
            }

            //verficar el estado is_active -> true par aoder eliminar.
            if ($user->is_active === true){
                throw new BusinessException('No puedes eliminar un usuario que esta activo.');
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
            // Aseguramos que solo un admin pueda ejecutar esta acción
            // Aunque ya está cubierta por el middleware 'can:admin' aplicado a todo excepto index/show,
            // es buena práctica tener una verificación explícita para acciones críticas.
            // if (!auth()->user()->can('admin')) {
            //     throw new BusinessException('No tienes permiso para realizar esta acción.', 403);
            // }

            $user = User::find($id);

            if (!$user) {
                throw new BusinessException('Usuario no encontrado.', 404);
            }

            // Opcional: Prevenir que un admin se inactive a sí mismo
            if ($user->id === auth()->id()) {
                throw new BusinessException('No puedes cambiar tu propio estado de actividad.', 403);
            }

            // La lógica para toggling: si está activo, se vuelve inactivo; si está inactivo, se vuelve activo.
            $user->is_active = !$user->is_active;
            $user->save();

            $status = $user->is_active ? 'activo' : 'inactivo';
            return ApiResponse::send(200,['message' => "El estado del ususario cambio a {$status} exitosamente.", $user]);

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


}
