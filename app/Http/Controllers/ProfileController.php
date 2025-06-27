<?php

namespace App\Http\Controllers;

use Throwable;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exceptions\BusinessException;
use Illuminate\Validation\ValidationException;
use App\Enums\UserRole; // Si necesitas referenciar roles en algún método
use Illuminate\Validation\Rule; // Agrega esta importación si no está

class ProfileController extends Controller
{
    /**
     * Constructor para asegurar que el usuario esté autenticado.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Obtener los datos del perfil del usuario autenticado.
     * GET /api/profile
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        // Obtener el usuario autenticado usando el helper auth()
        $user = auth()->user();

        if (!$user) {
            throw new BusinessException('Usuario no autenticado o perfil no encontrado.', 401);
        }

        $reponse = [
            'user' => $user,
            'email_verified' => $user->hasVerifiedEmail(),
        ];

        return ApiResponse::success($reponse);
    }

    /**
     * Actualizar los datos del perfil del usuario autenticado.
     * PATCH /api/profile
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new BusinessException('Usuario no autenticado o perfil no encontrado.', 401);
            }

            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                // El email puede actualizarse, pero debe seguir siendo único y no ser el del usuario actual
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                // La contraseña se maneja por separado si es necesario
            ]);

            $user->fill($validatedData);
            $user->save();

            return ApiResponse::success('Perfil actualizado exitosamente.', $user);

        } catch (ValidationException $e) {
            return ApiResponse::error('Errores de validación.', 422, $e->errors());
        } catch (Throwable $e) {
            \Log::error("Error al actualizar perfil de usuario {$user->id}: " . $e->getMessage(), ['exception' => $e]);
            return ApiResponse::serverError('Ocurrió un error inesperado al actualizar el perfil.');
        }
    }

    /**
     * Actualizar la contraseña del usuario autenticado.
     * PATCH /api/profile/password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                throw new BusinessException('Usuario no autenticado.', 401);
            }

            $validatedData = $request->validate([
                'current_password' => 'required|current_password', // Valida que la contraseña actual sea correcta
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user->password = bcrypt($validatedData['password']);
            $user->save();

            return ApiResponse::success('Contraseña actualizada exitosamente.');

        } catch (ValidationException $e) {
            return ApiResponse::error('Errores de validación.', 422, $e->errors());
        } catch (Throwable $e) {
            \Log::error("Error al actualizar contraseña de usuario {$user->id}: " . $e->getMessage(), ['exception' => $e]);
            return ApiResponse::serverError('Ocurrió un error inesperado al actualizar la contraseña.');
        }
    }

    /**
     * Obtener información específica para el manejo del perfil en el frontend.
     * Puedes agregar aquí métodos adicionales según las necesidades del frontend.
     * GET /api/profile/dashboard-info
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboardInfo(Request $request)
    {
        $user = $request->user();

        // Ejemplo: retornar el rol del usuario para lógica condicional en el frontend
        // y un mensaje de bienvenida.
        return ApiResponse::success('Información para el dashboard.', [
            'welcome_message' => 'Bienvenido, ' . $user->name . '!',
            'user_role' => $user->role->value, // Accede al valor string del Enum
            'is_active' => $user->is_active,
            // Puedes añadir más datos aquí que necesite tu frontend
            // 'notifications_count' => $user->unreadNotifications()->count(),
            // 'last_login' => $user->last_login,
        ]);
    }



}
