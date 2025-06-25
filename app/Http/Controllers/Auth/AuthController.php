<?php
// app/Http/Controllers/Auth/uthController
namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ResetPasswordCodeNotification;
use Illuminate\Support\Facades\Hash; // Para verificar contraseñas
use App\Http\Controllers\Controller;

class AuthController extends Controller
{

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
        // Revoca el token actual para el usuario autenticado
        $request->user()->currentAccessToken()->delete();

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
     * Registra un nuevo usuario. Solo accesible para administradores autenticados.
     * Requiere que el usuario autenticado tenga el rol 'admin'.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerUser(Request $request)
    {
        // El middleware 'can:admin' ya se encarga de la autorización.
        // Si llegamos aquí, el usuario es administrador.

        try{
            $request->validate([
                        'name' => 'required|string|max:255',
                        'email' => 'required|string|email|max:255|unique:users',
                        'password' => 'required|string|min:8|confirmed',
                        'role' => 'required|string|in:admin,empleado,gerente', // Define tus roles permitidos
                        // Añade más campos si tu modelo User los requiere (ej. departamento_id, etc.)
                    ]);

                    $user = User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'password' => Hash::make($request->password),
                        'role' => $request->role,
                        // ... otros campos
                    ]);

                    // Opcional: Generar un token para el usuario recién creado si quieres loguearlo automáticamente
                    // $token = $user->createToken('auth_token')->plainTextToken;

                    return response()->json([
                        'message' => 'Usuario registrado exitosamente.',
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role,
                        ],
                        // 'access_token' => $token, // Si decides generar token
                        // 'token_type' => 'Bearer',
                    ], 201); // 201 Created
        }catch(ValidationException $e ){
            return response()->json([
                'message' => 'Valitation Error',
                'error' => $e->errors()
            ], 422 );
        }catch ( \Excception $e ){
            return response()->json([
                'messgae'=> 'Ocurrio un Error durante el registro.',
                'error' => $e->getMesssage()
            ], 500);
        }

    }
}
