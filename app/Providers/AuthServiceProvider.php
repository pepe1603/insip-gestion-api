<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate; // Importa la fachada Gate
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy', // Aquí se mapean las políticas a los modelos
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define el Gate 'admin' para controlar el acceso por rol
        Gate::define('admin', function ($user) {
            // Asume que tu modelo User tiene una propiedad 'role'
            // y que 'admin' es el valor para los administradores.
            return $user->role === 'admin';
        });

        // Opcional: Define Gates adicionales si tienes más roles
         Gate::define('supervisor', function ($user) {
             return $user->role === 'supervisor' || $user->role === 'admin';
         });

        // Gate::define('empleado', function ($user) {
        //     return $user->role === 'empleado' || $user->role === 'gerente' || $user->role === 'admin';
        // });

        // Puedes agregar más Gates o configurar políticas aquí en el futuro.
    }
}
