<?php
// app/Http/Controllers/Auth/uthController
namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Enums\UserRole;
use App\Models\Empleado;
use Illuminate\Support\Str;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\BusinessException;
use App\Notifications\LoginNotification;
use App\Notifications\LogoutNotification;
use Illuminate\Validation\ValidationException;
use App\Notifications\RegisterUserNotification;
use App\Notifications\ResetPasswordCodeNotification;
use App\Notifications\PasswordResetSuccessNotification;
use App\Notifications\OtherDevicesLoggedOutNotification;
use Illuminate\Support\Facades\Hash; // Para verificar contraseñas

class AuthController extends Controller
{

  /**
     * Registra público de un nuevo usuario. Con asignación automática de roles.
     * Requiere que el usuario autenticado tenga el rol 'admin'.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            // Paso 1: Validación inicial de los datos de la solicitud.
            // La regla 'unique:users,email' se encarga de la primera validación de correo.
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Paso 2: Buscar si existe un empleado con ese correo.
            $empleado = Empleado::where('email', $validated['email'])->first();
            $role = UserRole::User->value; // Rol por defecto, se ajustará si es empleado.

            // Paso 3: Lógica de asignación de rol y confirmación.
            if ($empleado) {
                // Si el correo se encuentra en la base de empleados, asignamos el rol de 'Empleado'.
                if ($empleado->status === 'INACTIVO') {
                    // Lanzar una excepción de negocio si el empleado está inactivo.
                    throw new BusinessException("No se puede registrar un empleado que se encuentra Inactivo", 400); // Código 400 para BusinessException
                }
                $role = UserRole::Employee->value;
                $message = 'Correo encontrado en empleados. Se asignará el rol de "Empleado".';
            } else {
                // Si el correo NO se encuentra en la tabla de empleados.
                // Se requiere confirmación del frontend para registrar como usuario regular.
                if (!$request->boolean('confirmed_as_user')) {
                    return response()->json([
                        'message' => 'El correo no se encontró en la base de empleados. Si desea continuar, será registrado como un usuario regular.',
                        'role_suggested' => UserRole::User->value, // El rol sugerido para el frontend
                        'action_required' => true, // Indica al frontend que necesita confirmación
                    ], 200); // Se devuelve 200 para indicar que la solicitud fue procesada y se espera una acción.
                }

                // Si 'confirmed_as_user' es true, se procede a registrarlo como usuario normal.
                $role = UserRole::User->value;
                $message = 'Correo no encontrado en empleados. Se registrará como "Usuario" normal.';
            }

            // Paso 4: Crear el nuevo usuario con el rol correspondiente.
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $role,
                'is_active' => true,
                'empleado_id' => $empleado?->id, // Asigna el ID del empleado si existe
                'must_change_password' => false,
            ]);

            // Paso 5: Notificar al usuario.
            $loginUrl = env('APP_FRONTEND_LOGIN_URL', 'http://localhost:3000/login');
            $user->notify(new RegisterUserNotification($user, $loginUrl, $request->ip()));

            // Paso 6: Devolver una respuesta exitosa.
            return response()->json([
                'message' => 'Usuario registrado exitosamente.',
                'details' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role, // Incluir el rol final en la respuesta exitosa
                ]
            ], 201); // 201 Created es el código adecuado para la creación de recursos.

        } catch (ValidationException $e) {
            // Captura errores de validación de Laravel.
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $e->errors()
            ], 422); // 422 Unprocessable Entity para errores de validación.
        } catch (BusinessException $e) {
            // Captura excepciones de negocio personalizadas.
            // Asegúrate de que tu ApiResponse::send maneje el código HTTP correctamente.
            return ApiResponse::send($e->getCode(), $e->getMessage());
        } catch (\Exception $e) {
            // Captura cualquier otra excepción inesperada.
            Log::error('Error en registro público:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Error inesperado al registrar el usuario.',
                'error' => $e->getMessage(),
            ], 500); // 500 Internal Server Error para errores inesperados.
        }
    }

/**
 * ¿Qué hace esto?

*action_required: true: Indica que el frontend necesita mostrar un mensaje o interfaz en la que el
* usuario puede confirmar si quiere continuar con el registro como "Usuario". Esta es la manera en que el
* cliente puede interactuar con el usuario antes de completar el registro.
*Flexibilidad para el usuario: Esto te da la flexibilidad de ofrecer al usuario la opción de continuar con el
* registro como un "Usuario" si no está asociado con un empleado.
 */



   /**
     * Maneja la solicitud de login y genera un token Sanctum.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        //1.- Validar credenciales
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        //2.- Buscar usuario por email
        $user = User::where('email', $request->email)->first();

        // Si el usuario no existe
        if (!$user) {
            return response()->json(['message' => 'Email/Contraseña  invalidos'], 400);
        } 


        // Si el usuario está inactivo
        if (!$user->is_active) {
            return response()->json(['message' => 'Tu cuenta está inactiva. Contacta al administrador del sistema para más detalles.'], 403);
        }

        //3.- Verificar credenciales (contraseña)
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas. Por favor, verifica tu email y contraseña.'
            ], 404);
        }

        // --- lógica para must_change_password ---
        // 4.- Si las credenciales son válidas, verificar si debe cambiar la contraseña
        if ($user->must_change_password) {
            // Eliminar tokens existentes para evitar que el usuario acceda a otras rutas
            $user->tokens()->delete();

            // Generar un token temporal con la habilidad 'change-password'
            $temporaryToken = $user->createToken('temp_change_password_token')->plainTextToken;

            // Retornar una respuesta que indique al frontend que se requiere un cambio de contraseña
            return response()->json([
                'message' => 'Debe cambiar su contraseña para continuar.',
                'action_required' => 'CHANGE_PASSWORD', // Flag específico para el frontend
                'access_token' => $temporaryToken, // Token para la pantalla de cambio de contraseña
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ], 200); // Se usa 200 OK porque el login fue exitoso, pero requiere una acción.
        }
        // --- FIN: Nueva lógica para must_change_password ---

        // 5.- Si todo es válido y no se requiere cambio de contraseña, generar el token de Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6.- Notificar al usuario sobre el inicio de sesión
        $user->notify(new LoginNotification($user, $request->ip()));

        // 7.- Retornar el token y los datos del usuario en la respuesta
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'empleado_id'=>$user->empleado_id,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ]
        ], 200); // Código 200 OK para login exitoso
    }


    /**
     * Maneja el cambio de contraseña forzado para usuarios con 'must_change_password' activado.
     * Requiere un token con la habilidad 'change-password'.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forcePasswordChange(Request $request)
    {
        // El middleware 'auth:sanctum' y 'ability:change-password' ya garantizan
        // que el usuario está autenticado y que el token tiene la habilidad necesaria.
        $user = $request->user();

        try {
            // 1. Validar las nuevas contraseñas
            $request->validate([
                'current_password' => 'required|string', // La contraseña temporal
                'new_password' => 'required|string|min:8|confirmed', // La nueva contraseña
            ]);

            // 2. Verificar que la 'current_password' enviada coincida con la contraseña actual del usuario
            // (que sería la contraseña temporal asignada por el administrador).
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'La contraseña actual es incorrecta.'], 400);
            }

            // 3. Verificar que la nueva contraseña no sea la misma que la actual (opcional pero buena práctica)
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json(['message' => 'La nueva contraseña no puede ser igual a la contraseña actual.'], 400);
            }

            // 4. Actualizar la contraseña del usuario
            $user->password = Hash::make($request->new_password);
            $user->must_change_password = false; // Desactivar la bandera de cambio de contraseña
            $user->save();

            // 5. Revocar el token temporal y generar uno nuevo de acceso completo
            $user->tokens()->delete(); // Revoca todos los tokens, incluido el temporal.

            $newToken = $user->createToken('auth_token')->plainTextToken;

            // 6. Notificar al usuario sobre el cambio de contraseña exitoso (opcional)
            // Podrías reutilizar PasswordResetSuccessNotification o crear una nueva notificación.
            $user->notify(new PasswordResetSuccessNotification());

            // 7. Retornar una respuesta de éxito con el nuevo token de acceso completo
            return response()->json([
                'message' => 'Contraseña cambiada exitosamente. Ahora puede acceder a todas las funcionalidades.',
                'access_token' => $newToken,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'empleado_id'=>$user->empleado_id,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación al cambiar la contraseña.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en forcePasswordChange:', ['user_id' => $user->id ?? 'N/A', 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Ocurrió un error inesperado al intentar cambiar la contraseña.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Revoca el token actual del usuario autenticado (cierra la sesión).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Asegurarse de que hay un usuario autenticado (la ruta debe estar protegida)
        $user = $request->user();

        if (!$user) {
            // Esto solo se ejecutaría si la ruta no estuviera protegida,
            // pero es buena práctica defensiva.
            return response()->json(['message' => 'No hay usuario autenticado.'], 401);
        }

        // Revoca el token actual para el usuario autenticado
        $user->currentAccessToken()->delete();

        // Enviar la notificación de cierre de sesión
        $user->notify(new LogoutNotification()); // <-- Añade esta línea

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    /**
     * Solicita codigo de restablecimiento de contraseña y lo envia por email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        Log::info('forgotPassword: Solicitud recibida.', ['email' => $request->email]); // Log al inicio

        $request->validate(['email' => 'required|email']);

        // --- Punto de Verificación 1: ¿El email pasa la validación? ---
        // Si el correo electrónico no tiene el formato correcto, la validación fallará antes de este punto
        // y recibirás un error 422 de Laravel, no un 500.

        $user = User::where('email', $request->email)->first();

        // --- Punto de Verificación 2: ¿Se encontró el usuario? ---
        if ( !$user ) {
            Log::warning('forgotPassword: Usuario no encontrado para el email.', ['email' => $request->email]); // Log de advertencia
            return response()->json(['message' => 'Si su dirección de correo electrónico es válida, se le enviará un código de restablecimiento.'], 200);
        }

         if (!$user->is_active) {
            return response()->json(['message' => 'El usuario Se encuentra inactivo, no puedes recuperar tu cuenta, contacta con el adminisstrador del sisstema.'], 403);
        }

        // Si llegamos aquí, el usuario existe. Podemos registrar su ID o email.
        Log::info('forgotPassword: Usuario encontrado.', ['user_id' => $user->id, 'user_email' => $user->email]);

        // Genera un token corto
        $token = rand(100000, 999999); // Str::random(6); // O rand(100000, 999999) si prefieres numérico puro

        Log::info('forgotPassword: Token generado.', ['token' => $token]);

        // --- Punto de Verificación 3: ¿Se puede interactuar con la tabla password_reset_tokens? ---
        try {
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );
            Log::info('forgotPassword: Token almacenado/actualizado en la base de datos.', ['email' => $user->email]);
        } catch (\Exception $e) {
            // Captura cualquier error al interactuar con la DB
            Log::error('forgotPassword: Error al almacenar el token en la DB.', ['error' => $e->getMessage(), 'email' => $user->email]);
            // Re-lanza la excepción o devuelve un 500 para ver el stack trace completo
            return response()->json(['message' => 'Error interno del servidor al procesar la solicitud de restablecimiento.', 'error_detail' => $e->getMessage()], 500);
        }

        // --- Punto de Verificación 4: ¿Se puede enviar la notificación por correo? ---
        try {
            $user->notify(new ResetPasswordCodeNotification($token));
            Log::info('forgotPassword: Notificación de restablecimiento de contraseña enviada.', ['email' => $user->email]);
        } catch (\Exception $e) {
            // Captura cualquier error durante el envío del correo
            Log::error('forgotPassword: Error al enviar la notificación de restablecimiento.', ['error' => $e->getMessage(), 'email' => $user->email]);
            // Re-lanza la excepción o devuelve un 500 para ver el stack trace completo
            return response()->json(['message' => 'Error interno del servidor al enviar el correo de restablecimiento.', 'error_detail' => $e->getMessage()], 500);
        }


        return response()->json(['message' => 'Si su dirección de correo electrónico es válida, se le enviará un código de restablecimiento.'], 200);
    }

    /**
     * Verifica si el código de restablecimiento es válido para el email dado.
     * Esta es la NUEVA ruta intermedia.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string', // El código que el usuario ingresó
        ]);

        $passwordReset = DB::table('password_reset_tokens')
                            ->where('email', $request->email)
                            ->first();

        if (!$passwordReset || !Hash::check($request->code, $passwordReset->token)) {
            return response()->json(['message' => 'Código de verificación o email inválido.'], 400);
        }

        // Verificar si el token ha expirado (usando la configuración de auth.php)
        $expirationTime = now()->subMinutes(config('auth.passwords.users.expire'));
        if (\Carbon\Carbon::parse($passwordReset->created_at)->lt($expirationTime)) {
            // Opcional: Eliminar el token expirado si lo encuentras aquí
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Código de verificación expirado.'], 400);
        }

        // Si el código es válido y no ha expirado
        return response()->json(['message' => 'Código verificado exitosamente.'], 200);
    }


/**
     * Restablece la contraseña del usuario utilizando el código de verificación.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string', // El código de verificación
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $passwordReset = DB::table('password_reset_tokens')
                            ->where('email', $request->email)
                            ->first();

        if (!$passwordReset || !Hash::check($request->code, $passwordReset->token)) {
            return response()->json(['message' => 'Código de verificación inválido o expirado.'], 400);
        }

        // Verificar si el token ha expirado (re-verificación por seguridad)
        $expirationTime = now()->subMinutes(config('auth.passwords.users.expire'));
        if (\Carbon\Carbon::parse($passwordReset->created_at)->lt($expirationTime)) {
             DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Código de verificación expirado.'], 400);
        }

        // Actualizar contraseña
        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        // Eliminar el token después de usarlo
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Opcional: Revocar todos los tokens de Sanctum antiguos para el usuario
        $user->tokens()->delete();

                // *** Envía la notificación de éxito de restablecimiento de contraseña ***
        $user->notify(new PasswordResetSuccessNotification());

        return response()->json(['message' => 'Contraseña restablecida exitosamente.'], 200);
    }

    /**
     * Cierra la sesión del usuario en todos los dispositivos excepto el actual.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutOtherDevices(Request $request)
    {
        try {
            // Obtén el usuario autenticado actualmente
            $user = $request->user();

            if (!$user) {
                return ApiResponse::error('Usuario no autenticado.', 401);
            }
            $userAuthenticated = User::find($user->id);
            if (!$userAuthenticated) {
                return ApiResponse::error('Usuario no encontrado para cerrar sesión en otros dispositivos.', 404);
            }

            // Obtén el ID del token actual
            $currentAccessTokenId = $user->currentAccessToken()->id;

            // Elimina todos los tokens excepto el actual
            $user->tokens()
                 ->where('id', '!=', $currentAccessTokenId)
                 ->delete();

            // Envía la notificación simple de cierre de sesión en otros dispositivos
            $userAuthenticated->notify(new OtherDevicesLoggedOutNotification());

            // Opcional: puedes agregar un log para confirmar el envío
            Log::info('Notificación OtherDevicesLoggedOutNotification enviada al usuario.', ['user_id' => $userAuthenticated->id]);

            return ApiResponse::success('Sesión cerrada en todos los demás dispositivos exitosamente.');

        } catch (\Throwable $e) {
            Log::error('Error al cerrar sesión en otros dispositivos para el usuario ' . ($request->user()->id ?? 'N/A') . ': ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Ocurrió un error al intentar cerrar sesión en otros dispositivos. Por favor, inténtalo de nuevo más tarde.', 'exception' => $e], 500);

        }
    }

    public function markEmailAsVerified(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json(['message' => 'El correo ya ha sido verificado.'], 200);
        }

        $user->email_verified_at = now();
        $user->save();

        return response()->json(['message' => 'Correo verificado correctamente.']);
    }


}
