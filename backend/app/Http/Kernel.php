<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\TestMiddleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use App\Http\Middleware\TrustProxies;  // Asegúrate de importar esta clase
use App\Http\Middleware\EncryptCookies;  // Asegúrate de importar esta clase
use App\Http\Middleware\RedirectIfAuthenticated;  // Asegúrate de importar esta clase
use App\Http\Middleware\ConvertEmptyStringsToNull;  // Asegúrate de importar esta clase

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
protected $middleware = [
    TrustProxies::class,
    \Illuminate\Http\Middleware\HandleCors::class,
    \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
    \Illuminate\Http\Middleware\ValidatePostSize::class,
    ConvertEmptyStringsToNull::class,
    // ❌ Elimina estos:
    // \Illuminate\Session\Middleware\StartSession::class,
    // \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    // \Illuminate\Http\Middleware\SetCacheHeaders::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,

];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,  // Lo mismo aquí
            
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
           // \App\Http\Middleware\EnsureJsonResponse::class, //-> este era el erro de ErroConfuse_ del que crasheaba el setgvidor y nod ejaba realziar solicitudes.
            //\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, //solo si usamos Sanctum para spa/SSR, no para tokens puros
            //'auth', //solo si quieremos aplicar autherntication a toas las rutas api del grupo
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            //HandleCors::class,
            
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'guest' => RedirectIfAuthenticated::class,  // Asegúrate de importar esta clase también
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        //'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
        //'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,

    ];
}
