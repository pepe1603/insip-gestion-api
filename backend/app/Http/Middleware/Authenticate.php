<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use App\Exceptions\AuthTokenException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\Middleware\Authenticate as Middleware;


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
     * Handle an incoming request.
     * Añadimos un dd() aquí para confirmar si este middleware se está ejecutando.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$guards
     * @return mixed
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // --- ¡AÑADE ESTO TEMPORALMENTE! ---
        dd('Executing App\Http\Middleware\Authenticate handle method');
        // ------------------------------------

        return parent::handle($request, $next, ...$guards);
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


 /**
     * Handle an unauthenticated user.
     * Sobrescribimos este método para lanzar nuestra excepción personalizada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     * @throws \Illuminate\Auth\AuthenticationException | \App\Exceptions\AuthTokenException
     */
    protected function unauthenticated($request, array $guards): void
    {

        if ($request->expectsJson()) {
            $reason = 'no_token';
            if ($request->bearerToken()) {
                $reason = 'invalid_or_expired_token';
            }
            // Lanza tu excepción personalizada con la razón.
            throw new AuthTokenException('Authentication failed', $reason, Response::HTTP_UNAUTHORIZED);
        }

        // Si no es JSON, sigue el comportamiento por defecto (que lanzará AuthenticationException).
        // Si no quieres que tu Handler maneje las redirecciones web,
        // podrías también lanzar aquí una excepción genérica o dejar solo el redirectTo.
        parent::unauthenticated($request, $guards); // Esto lanzará la AuthenticationException de Laravel base si no es JSON.
    }

    // La implementación base de Laravel de unauthenticated() ya lanza la AuthenticationException
    // que tu Handler.php puede interceptar.
}
