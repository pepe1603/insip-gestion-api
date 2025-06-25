<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Symfony\Component\HttpFoundation\Response; // Asegúrate de importar Response

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * Este método es llamado por el método unauthenticated de la clase padre
     * si la solicitud NO espera JSON, O si se usa el guard 'web'.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Si la solicitud espera JSON, NO redirigimos. Retornamos null
        // para que la excepción de autenticación sea lanzada y capturada
        // por el manejador de excepciones (Handler.php) o el método unauthenticated.
        if ($request->expectsJson()) {
            return null; // ¡CORREGIDO! No devuelve respuesta JSON aquí.
        }

        // Si no espera JSON (ej. petición de navegador para una ruta web),
        // y tuvieras una ruta 'login' definida, podrías redirigir.
        // Como no la usas, podrías simplemente retornar null o una ruta de error genérica.
        // Para tu caso de API pura, 'route('welcome')' es una opción si quieres
        // un fallback para navegadores no esperados.
        return route('welcome'); // O null si prefieres no redirigir a ninguna parte.
    }

    /**
     * Handle an unauthenticated user.
     * Este método se ejecuta cuando un usuario no está autenticado y se intenta acceder a una ruta protegida.
     * Lo sobrescribimos para devolver una respuesta JSON en lugar de intentar una redirección.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array<string>  $guards
     * @return void
     *
     * @throws AuthenticationException
     */
    protected function unauthenticated($request, array $guards): void
    {
        // Si la solicitud es de tipo API (es decir, espera una respuesta JSON)
        // o si la ruta es parte de tu prefijo 'api/',
        // abortamos la ejecución con una respuesta HTTP 401 Unauthorized y un mensaje JSON.
        if ($request->expectsJson() || $request->is('api/*')) {
            abort(response()->json(['message' => 'No autorizado. Se requiere autenticación.'], Response::HTTP_UNAUTHORIZED));
        }

        // Si la solicitud NO espera JSON y NO es una ruta API (ej. solicitud web tradicional),
        // lanzamos la excepción para que el flujo de Laravel (incluyendo el redirectTo)
        // se encargue de la redirección.
        parent::unauthenticated($request, $guards);
    }
}
