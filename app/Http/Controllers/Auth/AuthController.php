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
use App\Notifications\RegisterUserNotification;
use App\Notifications\ResetPasswordCodeNotification;
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
public function signIn(Request $request)
{
    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',//busca campopassword_confirmation
        ]);

        // --- Validación 1: ¿El correo ya está registrado en la base de usuarios? ---
        $existingUser = User::where('email', $validated['email'])->first();

        // Si el correo ya está registrado, devolver un mensaje de error
        if ($existingUser) {
            return response()->json([
                'message' => 'El correo electrónico ya está registrado en el sistema. Por favor, utiliza otro correo o inicia sesión.',
            ], 400); // Código HTTP 400 - Bad Request
        }

        // Buscar si existe un empleado con ese correo
        $empleado = Empleado::where('email', $validated['email'])->first();

        // --- Validación 2: Si el correo se encuentra en la base de empleados, asignamos el rol de 'Empleado' ---
        if ($empleado) {
            $role = UserRole::Employee->value;
            $message = 'Correo encontrado en empleados. Se asignará el rol de "Empleado".';
        } else {
            // Si no se encuentra, verificar si el usuario ya confirmó continuar como "Usuario"
            if (!$request->boolean('confirmed_as_user')) {
                $role = UserRole::User->value;
                $message = 'No encontramos el correo en la base de empleados. Si desea continuar, será registrado como <Usuario> .';

                return response()->json([
                    'message' => $message,
                    'role' => $role,
                    'action_required' => true, // Indica que se necesita confirmación
                ], 200);
            }

        if ($empleado && $empleado->status === 'INACTIVO'){
            throw new BusinessException("No se puede registrar un empleado que se encuentra Inactivo");
        }

            // Si confirmó, se procede como "Usuario"
            $role = UserRole::User->value;
            $message = 'Correo no encontrado en empleados. Se registrará como "Usuario".';
        }


        // Crear el nuevo usuario con el rol correspondiente
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'is_active' => true,
            'empleado_id' => $empleado?->id, // Si hay un empleado asociado, asignamos el ID
        ]);

        // Notificar al usuario
        $loginUrl = env('APP_FRONTEND_LOGIN_URL', 'http://localhost:3000/login');
        $user->notify(new RegisterUserNotification($user, $loginUrl, $request->ip()));

        // Devolver una respuesta exitosa con los datos del usuario registrado
        return response()->json([
            'message' => 'Usuario registrado exitosamente.',
            'details' => [
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 201); // Código HTTP 201 - Created

    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Error de validación.',
            'errors' => $e->errors()
        ], 422);
    } catch (BusinessException $e) {
        return ApiResponse::send($e->getCode(), $e->getMessage());
    } catch (\Exception $e) {
        Log::error('Error en registro público:', ['error' => $e->getMessage()]);
        return response()->json([
            'message' => 'Error inesperado al registrar el usuario.', $e,
            'error' => $e->getMessage(),
        ], 500);
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

        //1.- validar
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        //2.- Buscar usuario por email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        //3.- Verificar credenciales
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas. Porfavor verifica tu email y contraseña. '
            ], 401);
        }

        //3.1
        //Desvincular tokens anteriores: Si un usuario ya tiene un token y vuelve a autenticarse, puedes revocar
        // los tokens previos antes de generar uno nuevo:
        // $user->tokens->each(function ($token) {
        //     $token->delete();
        // });



        //4.- Si las credenciales son validas, generar el token de sanctum
         // Puedes darle un nombre al token (ej. 'auth_token') y definir habilidades si las necesitas.
        // Genera el token. Puedes especificar habilidades si las necesitas.
        $token = $user->createToken('auth_token')->plainTextToken;
        //4.1 Notificar al usuario
        $user->notify(new LoginNotification($user, $request->ip()));

        //5.- retorna  el token en al respuesta
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role, // Asegúrate de tener una columna 'role' en tu tabla users

            ]
        ]);
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

        // Si llegamos aquí, el usuario existe. Podemos registrar su ID o email.
        Log::info('forgotPassword: Usuario encontrado.', ['user_id' => $user->id, 'user_email' => $user->email]);

        // Genera un token corto
        $token = Str::random(6); // O rand(100000, 999999) si prefieres numérico puro

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

            // Obtén el ID del token actual que está siendo usado para esta petición
            // Sanctum proporciona acceso al token actual a través de request->user()->currentAccessToken()
            $currentAccessTokenId = $user->currentAccessToken()->id;

            // Elimina todos los tokens del usuario, excepto el que se está usando actualmente
            $user->tokens()
                 ->where('id', '!=', $currentAccessTokenId)
                 ->delete();

            // Opcional: Podrías enviar una notificación al usuario indicándole que sus sesiones fueron cerradas en otros dispositivos.
            // $user->notify(new SessionsLoggedOutNotification());

            return ApiResponse::success('Sesión cerrada en todos los demás dispositivos exitosamente.', null, 200);

        } catch (\Throwable $e) {
            // Registra el error
            Log::error('Error al cerrar sesión en otros dispositivos para el usuario ' . ($request->user()->id ?? 'N/A') . ': ' . $e->getMessage(), ['exception' => $e]);

            return ApiResponse::serverError('Ocurrió un error al intentar cerrar sesión en otros dispositivos.');
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
