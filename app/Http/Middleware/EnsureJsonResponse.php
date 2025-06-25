<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        // Si la solicitud no es JSON, devolverá un error
        if (!$request->expectsJson()) {
            return response()->json([
                'message' => 'Formato no aceptado. Solo JSON es permitido.',
            ], 406); // 406 Not Acceptable
        }

        return $next($request);
    }
}
